<?php

/**
 * Worker untuk ProjectApplyConcurrencyTest: satu proses PHP = satu talenta yang menekan
 * tombol Apply. Semua worker menunggu sampai timestamp yang sama lalu apply bersamaan.
 *
 * Pemakaian: php apply_race_worker.php <projectId> <userId> <startAtUnixFloat> [apply|cancel]
 * Konfigurasi DB lewat env (DB_CONNECTION, DB_DATABASE, ...). Wajib RACE_EXPECTED_DB.
 * Output: satu baris JSON {"ok":bool, "reason"?:string, "error"?:string}.
 */

use App\Exceptions\ApplyNotAllowedException;
use App\Exceptions\CancelNotAllowedException;
use App\Models\User;
use App\Services\ProjectApplyService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

[$script, $projectId, $userId, $startAt, $action] = $argv + [null, null, null, null, 'apply'];

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Pengaman: jangan pernah menulis ke database selain database uji yang diminta.
$expected = getenv('RACE_EXPECTED_DB');
$actual = config('database.connections.' . config('database.default') . '.database');
if (! $expected || $actual !== $expected) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => "Refusing to run against database '{$actual}'."]));
    exit(2);
}

$user = User::findOrFail((int) $userId);
$service = $app->make(ProjectApplyService::class);
DB::connection()->getPdo(); // buka koneksi sebelum garis start

while (microtime(true) < (float) $startAt) {
    usleep(200);
}

try {
    if ($action === 'cancel') {
        $service->cancel((int) $projectId, $user);
    } else {
        $service->apply((int) $projectId, $user, ['message' => 'race']);
    }
    $result = ['ok' => true];
} catch (ApplyNotAllowedException $e) {
    $result = ['ok' => false, 'reason' => $e->reason()];
} catch (CancelNotAllowedException $e) {
    $result = ['ok' => false, 'reason' => $e->reason];
} catch (Throwable $e) {
    $result = ['ok' => false, 'error' => get_class($e) . ': ' . $e->getMessage()];
}

fwrite(STDOUT, json_encode($result));
