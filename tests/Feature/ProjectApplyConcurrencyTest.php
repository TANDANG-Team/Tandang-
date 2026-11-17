<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Services\ProjectApplyService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesApplyFixtures;
use Tests\TestCase;
use Throwable;

/**
 * Race condition sungguhan: beberapa proses PHP terpisah melamar ke slot terakhir pada saat
 * yang sama. Tidak memakai RefreshDatabase (transaksi test tidak terlihat oleh proses lain);
 * sebagai gantinya memakai database uji khusus yang dibuat & dibuang oleh test ini.
 *
 * - sqlite: selalu jalan (file di storage/framework/testing).
 * - mysql : opt-in, set RACE_TEST_MYSQL=1 (memakai DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD
 *           dari RACE_MYSQL_* atau default 127.0.0.1:3306 root tanpa password) dan membuat
 *           database terpisah `tandang_apply_race_test`, bukan database aplikasi.
 */
class ProjectApplyConcurrencyTest extends TestCase
{
    use CreatesApplyFixtures;

    private const MYSQL_DATABASE = 'tandang_apply_race_test';
    private const WORKERS = 6;

    private array $workerEnv = [];
    private ?string $sqliteFile = null;
    private ?PDO $mysqlAdmin = null;

    public static function drivers(): array
    {
        return ['sqlite' => ['sqlite'], 'mysql' => ['mysql']];
    }

    protected function tearDown(): void
    {
        DB::purge('race');
        if ($this->mysqlAdmin) {
            $this->mysqlAdmin->exec('DROP DATABASE IF EXISTS `' . self::MYSQL_DATABASE . '`');
        }
        if ($this->sqliteFile) {
            foreach (glob($this->sqliteFile . '*') as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    #[DataProvider('drivers')]
    public function test_concurrent_applies_for_last_slot_only_one_succeeds(string $driver): void
    {
        $this->useSharedDatabase($driver);

        // Butuh 2 talenta → batas 4 pelamar aktif; 3 sudah melamar → tinggal 1 slot.
        $project = $this->makeProject($this->makeProvider(), 2);
        foreach ($this->makeTalents(3) as $talent) {
            ProjectApplication::create(['project_id' => $project->id, 'user_id' => $talent->id, 'status' => 'menunggu']);
        }
        $racers = $this->makeTalents(self::WORKERS);

        $results = $this->race($project, array_map(fn (User $u) => $u->id, $racers));

        $this->assertSame([], array_values(array_filter($results, fn ($r) => isset($r['error']))), 'Worker error: ' . json_encode($results));
        $this->assertCount(1, array_filter($results, fn ($r) => $r['ok']), 'Tepat satu talenta boleh lolos: ' . json_encode($results));
        $this->assertCount(self::WORKERS - 1, array_filter($results, fn ($r) => ($r['reason'] ?? null) === ProjectApplyService::REASON_QUOTA_FULL));
        $this->assertSame(4, $project->applications()->whereIn('status', ProjectApplication::ACTIVE_STATUSES)->count());
    }

    #[DataProvider('drivers')]
    public function test_concurrent_double_apply_by_same_talent_creates_one_application(string $driver): void
    {
        $this->useSharedDatabase($driver);

        $project = $this->makeProject($this->makeProvider(), 3);
        $talent = $this->makeTalent();

        $results = $this->race($project, array_fill(0, 4, $talent->id));

        $this->assertSame([], array_values(array_filter($results, fn ($r) => isset($r['error']))), 'Worker error: ' . json_encode($results));
        $this->assertCount(1, array_filter($results, fn ($r) => $r['ok']));
        $this->assertCount(3, array_filter($results, fn ($r) => ($r['reason'] ?? null) === ProjectApplyService::REASON_ALREADY_APPLIED));
        $this->assertSame(1, ProjectApplication::where('project_id', $project->id)->count());
        $this->assertSame(1, DB::table('applications')->where('project_id', $project->id)->count());
    }

    #[DataProvider('drivers')]
    public function test_concurrent_cancellations_cannot_exceed_weekly_limit(string $driver): void
    {
        $this->useSharedDatabase($driver);

        // Sudah 1 pembatalan minggu ini → sisa 2. Talenta lalu membatalkan 4 lamaran sekaligus.
        $provider = $this->makeProvider();
        $talent = $this->makeTalent();
        $used = $this->makeProject($provider, 1);
        ProjectApplication::create([
            'project_id' => $used->id, 'user_id' => $talent->id,
            'status' => ProjectApplication::STATUS_DIBATALKAN, 'cancelled_at' => now()->subDay(),
        ]);
        $targets = [];
        for ($i = 0; $i < 4; $i++) {
            $project = $this->makeProject($provider, 2);
            ProjectApplication::create(['project_id' => $project->id, 'user_id' => $talent->id, 'status' => 'menunggu']);
            $targets[] = [$project->id, $talent->id];
        }

        $results = $this->runWorkers($targets, 'cancel');

        $this->assertSame([], array_values(array_filter($results, fn ($r) => isset($r['error']))), 'Worker error: ' . json_encode($results));
        $this->assertCount(2, array_filter($results, fn ($r) => $r['ok']), 'Hanya sisa jatah yang boleh terpakai: ' . json_encode($results));
        $this->assertCount(2, array_filter($results, fn ($r) => ($r['reason'] ?? null) === ProjectApplyService::CANCEL_REASON_LIMIT_REACHED));
        $this->assertSame(3, ProjectApplication::where('user_id', $talent->id)->whereNotNull('cancelled_at')->count());
    }

    /**
     * Jalankan satu worker per userId secara paralel dan kembalikan hasil JSON masing-masing.
     */
    private function race(Project $project, array $userIds): array
    {
        return $this->runWorkers(array_map(fn ($userId) => [$project->id, $userId], $userIds), 'apply');
    }

    /**
     * @param  array<array{0:int,1:int}>  $targets  pasangan [projectId, userId], satu proses per pasangan
     */
    private function runWorkers(array $targets, string $action): array
    {
        $worker = base_path('tests/Support/apply_race_worker.php');
        $startAt = sprintf('%.6F', microtime(true) + 4.0); // cukup untuk semua worker selesai boot
        $env = array_merge(getenv(), $this->workerEnv);

        $procs = [];
        foreach ($targets as [$projectId, $userId]) {
            $cmd = [PHP_BINARY, $worker, (string) $projectId, (string) $userId, $startAt, $action];
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $env);
            $this->assertIsResource($proc, 'Gagal menjalankan worker');
            $procs[] = [$proc, $pipes];
        }

        $results = [];
        foreach ($procs as [$proc, $pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
            $decoded = json_decode(trim($out), true);
            $results[] = is_array($decoded) ? $decoded : ['ok' => false, 'error' => trim($out . ' ' . $err)];
        }

        return $results;
    }

    private function useSharedDatabase(string $driver): void
    {
        if ($driver === 'sqlite') {
            if (PHP_VERSION_ID < 80400) {
                $this->markTestSkipped('transaction_mode IMMEDIATE SQLite hanya diterapkan Laravel di PHP >= 8.4.');
            }
            $dir = storage_path('framework/testing');
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $this->sqliteFile = $dir . DIRECTORY_SEPARATOR . 'apply_race_' . getmypid() . '.sqlite';
            touch($this->sqliteFile);

            // Pakai konfigurasi koneksi sqlite aplikasi (termasuk transaction_mode) agar yang diuji = yang dipakai.
            $connection = array_merge(config('database.connections.sqlite'), ['database' => $this->sqliteFile, 'url' => null]);
            $this->workerEnv = ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $this->sqliteFile, 'RACE_EXPECTED_DB' => $this->sqliteFile];
        } else {
            if (! getenv('RACE_TEST_MYSQL')) {
                $this->markTestSkipped('Set RACE_TEST_MYSQL=1 untuk menjalankan uji race di MySQL.');
            }
            $host = getenv('RACE_MYSQL_HOST') ?: '127.0.0.1';
            $port = getenv('RACE_MYSQL_PORT') ?: '3306';
            $user = getenv('RACE_MYSQL_USERNAME') ?: 'root';
            $pass = getenv('RACE_MYSQL_PASSWORD') ?: '';
            try {
                $this->mysqlAdmin = new PDO("mysql:host={$host};port={$port}", $user, $pass);
            } catch (Throwable $e) {
                $this->markTestSkipped('MySQL tidak tersedia: ' . $e->getMessage());
            }
            $this->mysqlAdmin->exec('DROP DATABASE IF EXISTS `' . self::MYSQL_DATABASE . '`');
            $this->mysqlAdmin->exec('CREATE DATABASE `' . self::MYSQL_DATABASE . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            $connection = array_merge(config('database.connections.mysql'), [
                'host' => $host, 'port' => $port, 'database' => self::MYSQL_DATABASE,
                'username' => $user, 'password' => $pass, 'url' => null,
            ]);
            $this->workerEnv = [
                'DB_CONNECTION' => 'mysql', 'DB_HOST' => $host, 'DB_PORT' => $port,
                'DB_DATABASE' => self::MYSQL_DATABASE, 'DB_USERNAME' => $user, 'DB_PASSWORD' => $pass,
                'RACE_EXPECTED_DB' => self::MYSQL_DATABASE,
            ];
        }

        $this->workerEnv += ['DB_URL' => '', 'APP_ENV' => 'testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'];

        config(['database.connections.race' => $connection, 'database.default' => 'race']);
        DB::purge('race');
        Artisan::call('migrate:fresh', ['--database' => 'race', '--force' => true]);
    }
}
