<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TalentProfile;

class WorkstyleMatchingService
{
    public const WORKSTYLE_KEYS = [
        'collaboration',
        'structure',
        'communication',
        'environment',
        'taskPreference',
        'workPace',
        'socialInteraction',
        'planning',
    ];

    public const QUESTIONS = [
        [
            'key' => 'collaboration',
            'title' => 'Saat mengerjakan proyek, kamu lebih nyaman bekerja seperti apa?',
            'low' => 'Mandiri',
            'high' => 'Bersama tim',
            'options' => [
                1 => 'Sangat nyaman bekerja sendiri',
                2 => 'Lebih nyaman bekerja sendiri',
                3 => 'Fleksibel, keduanya cocok',
                4 => 'Lebih nyaman bekerja bersama tim',
                5 => 'Sangat nyaman bekerja dalam tim',
            ],
        ],
        [
            'key' => 'structure',
            'title' => 'Bagaimana kamu lebih suka menjalankan tugas?',
            'low' => 'Terstruktur',
            'high' => 'Bebas',
            'options' => [
                1 => 'Sangat menyukai instruksi dan tahapan yang jelas',
                2 => 'Lebih nyaman dengan pekerjaan terstruktur',
                3 => 'Fleksibel mengikuti kebutuhan',
                4 => 'Lebih nyaman dengan cara kerja fleksibel',
                5 => 'Sangat menyukai kebebasan menentukan cara kerja',
            ],
        ],
        [
            'key' => 'communication',
            'title' => 'Bagaimana cara komunikasi kerja yang paling nyaman untukmu?',
            'low' => 'Tertulis',
            'high' => 'Langsung',
            'options' => [
                1 => 'Sangat menyukai komunikasi tertulis',
                2 => 'Lebih nyaman melalui pesan tertulis',
                3 => 'Nyaman dengan keduanya',
                4 => 'Lebih nyaman berdiskusi langsung',
                5 => 'Sangat menyukai komunikasi langsung dan aktif',
            ],
        ],
        [
            'key' => 'environment',
            'title' => 'Di lingkungan kerja seperti apa kamu lebih nyaman?',
            'low' => 'Jarak jauh',
            'high' => 'Lapangan',
            'options' => [
                1 => 'Sangat nyaman bekerja jarak jauh',
                2 => 'Lebih nyaman bekerja jarak jauh',
                3 => 'Nyaman dengan keduanya',
                4 => 'Lebih nyaman bekerja langsung di lokasi',
                5 => 'Sangat menyukai aktivitas kerja lapangan',
            ],
        ],
        [
            'key' => 'taskPreference',
            'title' => 'Jenis aktivitas apa yang lebih kamu sukai?',
            'low' => 'Analitis',
            'high' => 'Praktis',
            'options' => [
                1 => 'Sangat menyukai pekerjaan administratif atau analitis',
                2 => 'Lebih nyaman dengan tugas administratif',
                3 => 'Nyaman dengan keduanya',
                4 => 'Lebih menyukai aktivitas operasional',
                5 => 'Sangat menyukai pekerjaan praktis dan aktif',
            ],
        ],
        [
            'key' => 'workPace',
            'title' => 'Bagaimana ritme kerja yang paling nyaman untukmu?',
            'low' => 'Stabil',
            'high' => 'Dinamis',
            'options' => [
                1 => 'Sangat menyukai ritme stabil dan terencana',
                2 => 'Lebih nyaman dengan jadwal yang teratur',
                3 => 'Fleksibel mengikuti situasi',
                4 => 'Menyukai pekerjaan yang cukup dinamis',
                5 => 'Sangat menyukai lingkungan kerja cepat dan dinamis',
            ],
        ],
        [
            'key' => 'socialInteraction',
            'title' => 'Seberapa nyaman kamu berinteraksi dengan orang baru saat bekerja?',
            'low' => 'Terbatas',
            'high' => 'Intens',
            'options' => [
                1 => 'Sangat nyaman dengan interaksi terbatas',
                2 => 'Lebih nyaman dengan sedikit interaksi',
                3 => 'Cukup nyaman berinteraksi',
                4 => 'Nyaman bertemu dan melayani orang baru',
                5 => 'Sangat menikmati interaksi sosial yang intens',
            ],
        ],
        [
            'key' => 'planning',
            'title' => 'Bagaimana kamu biasanya lebih suka mengatur pekerjaan?',
            'low' => 'Rinci',
            'high' => 'Spontan',
            'options' => [
                1 => 'Sangat menyukai rencana kerja yang rinci',
                2 => 'Lebih nyaman dengan rencana yang jelas',
                3 => 'Fleksibel menyesuaikan situasi',
                4 => 'Lebih nyaman menyesuaikan rencana saat bekerja',
                5 => 'Sangat menyukai pengaturan kerja yang spontan',
            ],
        ],
    ];

    /**
     * Pemetaan karakteristik gaya kerja dari form Penyedia ke dimensi asesmen talenta.
     * Nilai kiri = ujung "rendah" (1–2), kanan = ujung "tinggi" (4–5), Keduanya = tengah (3).
     */
    public const CHARACTERISTIC_MAP = [
        'independence_teamwork'          => ['key' => 'collaboration',     'left' => 'Mandiri',            'right' => 'Kerja tim'],
        'structure_flexibility'          => ['key' => 'structure',         'left' => 'Terstruktur',        'right' => 'Fleksibel'],
        'written_verbal'                 => ['key' => 'communication',     'left' => 'Tertulis',           'right' => 'Langsung'],
        'remote_field'                   => ['key' => 'environment',       'left' => 'Remote',             'right' => 'Lapangan'],
        'analytical_practical'           => ['key' => 'taskPreference',    'left' => 'Analitis',           'right' => 'Praktis'],
        'stable_dynamic'                 => ['key' => 'workPace',          'left' => 'Stabil',             'right' => 'Dinamis'],
        'limited_intense_interaction'    => ['key' => 'socialInteraction', 'left' => 'Interaksi terbatas', 'right' => 'Interaksi intens'],
        'detailed_spontaneous_planning'  => ['key' => 'planning',          'left' => 'Rencana rinci',      'right' => 'Spontan'],
    ];

    public const BALANCED_LABEL = 'Keduanya';

    /**
     * Ubah karakteristik pilihan Penyedia (teks) menjadi kebutuhan numerik 1–5.
     */
    public static function characteristicsToRequirements(?array $characteristics): array
    {
        $requirements = [];
        foreach (self::CHARACTERISTIC_MAP as $charKey => $map) {
            $value = $characteristics[$charKey] ?? null;
            if ($value === $map['left']) {
                $requirements[$map['key']] = 2;
            } elseif ($value === $map['right']) {
                $requirements[$map['key']] = 4;
            } elseif ($value === self::BALANCED_LABEL) {
                $requirements[$map['key']] = 3;
            }
        }

        return $requirements;
    }

    /**
     * Kebutuhan gaya kerja proyek: pakai workstyle_requirements, atau turunkan dari karakteristik.
     */
    public function requirementsFor(Project $project): array
    {
        $requirements = $project->workstyle_requirements ?? [];
        if (is_string($requirements)) {
            $requirements = json_decode($requirements, true) ?? [];
        }

        if (empty($requirements)) {
            $characteristics = $project->workstyle_characteristics ?? [];
            if (is_string($characteristics)) {
                $characteristics = json_decode($characteristics, true) ?? [];
            }
            $requirements = self::characteristicsToRequirements($characteristics);
        }

        return $requirements;
    }

    /**
     * Pastikan jawaban asesmen berupa integer 1–5 dan hanya berisi dimensi yang dikenal.
     */
    public static function normalizeAnswers($answers): array
    {
        if (is_string($answers)) {
            $answers = json_decode($answers, true) ?? [];
        }

        $clean = [];
        foreach (self::WORKSTYLE_KEYS as $key) {
            if (isset($answers[$key]) && is_numeric($answers[$key])) {
                $clean[$key] = max(1, min(5, (int) $answers[$key]));
            }
        }

        return $clean;
    }

    public static function isComplete($answers): bool
    {
        return count(self::normalizeAnswers($answers)) === count(self::WORKSTYLE_KEYS);
    }

    /**
     * Hitung skor kecocokan antara talenta dan proyek (0 - 100)
     */
    public function calculateMatch(?TalentProfile $talent, Project $project): array
    {
        $components = [];

        // 1. Skill / Kompetensi (Bobot 45%)
        $projectSkills = $project->skills ?? [];
        if (is_string($projectSkills)) {
            $projectSkills = json_decode($projectSkills, true) ?? [];
        }

        $talentSkills = $talent?->skills ?? [];
        if (is_string($talentSkills)) {
            $talentSkills = json_decode($talentSkills, true) ?? [];
        }

        $matchedSkills = [];
        $lowerTalentSkills = array_map('strtolower', $talentSkills);

        foreach ($projectSkills as $s) {
            if (in_array(strtolower($s), $lowerTalentSkills, true)) {
                $matchedSkills[] = $s;
            }
        }

        $skillsScore = null;
        if (!empty($projectSkills)) {
            $skillsScore = (count($matchedSkills) / count($projectSkills)) * 100;
        }

        $components['skills'] = [
            'label' => 'Kompetensi',
            'value' => $skillsScore,
            'weight' => 45,
            'detail' => !empty($projectSkills)
                ? count($matchedSkills) . ' dari ' . count($projectSkills) . ' kompetensi sesuai'
                : 'Kompetensi proyek belum ditentukan',
        ];

        // 2. Gaya Kerja / Workstyle (Bobot 25%)
        $answers = self::normalizeAnswers($talent?->assessment_answers ?? []);
        $requirements = $this->requirementsFor($project);

        $workstyleScore = null;
        if (!empty($answers) && !empty($requirements)) {
            $matchingDims = [];
            foreach (self::WORKSTYLE_KEYS as $key) {
                if (isset($answers[$key], $requirements[$key])) {
                    $diff = abs((int) $answers[$key] - (int) $requirements[$key]);
                    $dimScore = 1 - ($diff / 4.0);
                    $matchingDims[] = $dimScore;
                }
            }

            if (!empty($matchingDims)) {
                $workstyleScore = (array_sum($matchingDims) / count($matchingDims)) * 100;
            }
        }

        $components['workstyle'] = [
            'label' => 'Gaya Kerja',
            'value' => $workstyleScore,
            'weight' => 25,
            'detail' => $workstyleScore !== null
                ? round($workstyleScore) . '% preferensi kerja selaras'
                : 'Asesmen atau kebutuhan gaya kerja belum diisi',
        ];

        // 3. Lokasi & Mode Kerja (Bobot 15%)
        $locationScore = null;
        $city = $talent?->city;
        $workMode = $talent?->work_mode ?? 'Keduanya';
        $pLocation = $project->location ?? 'Remote';

        $isRemoteProject = stripos($pLocation, 'remote') !== false;

        if ($isRemoteProject) {
            $locationScore = ($workMode === 'Onsite') ? 20 : 100;
        } elseif ($city && stripos($pLocation, $city) !== false) {
            $locationScore = ($workMode === 'Remote') ? 40 : 100;
        } else {
            $locationScore = ($workMode === 'Remote') ? 20 : 55;
        }

        $components['location'] = [
            'label' => 'Lokasi',
            'value' => $locationScore,
            'weight' => 15,
            'detail' => $locationScore >= 80 ? 'Lokasi & mode kerja sesuai' : 'Lokasi atau mode kerja berbeda',
        ];

        // 4. Jadwal / Availability (Bobot 15%)
        $availability = $talent?->availability ?? 'Fleksibel';
        $pSchedule = $project->schedule ?? 'Fleksibel';
        $availScore = ($pSchedule === 'Fleksibel' || $availability === 'Fleksibel' || $availability === $pSchedule)
            ? 100
            : 45;

        $components['availability'] = [
            'label' => 'Jadwal',
            'value' => $availScore,
            'weight' => 15,
            'detail' => $availScore >= 80 ? 'Jadwal kerja sesuai' : 'Jadwal perlu disesuaikan',
        ];

        // Hitung skor total rata-rata tertimbang (hanya komponen yang memiliki nilai)
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($components as $comp) {
            if ($comp['value'] !== null) {
                $totalWeight += $comp['weight'];
                $weightedSum += ($comp['value'] * $comp['weight']);
            }
        }

        $finalScore = $totalWeight > 0 ? (int) round($weightedSum / $totalWeight) : 0;

        return [
            'score' => $finalScore,
            'matched_skills' => $matchedSkills,
            'components' => $components,
            'completeness' => $totalWeight,
            'is_partial' => $totalWeight < 100,
        ];
    }

    /**
     * Hasilkan kalimat ringkasan gaya kerja berdasarkan jawaban asesmen
     */
    public function generateSummary($answers): string
    {
        $a = self::normalizeAnswers($answers);
        $pick = fn (string $key, string $high, string $low, string $mid) =>
            ($a[$key] ?? 3) >= 4 ? $high : (($a[$key] ?? 3) <= 2 ? $low : $mid);

        $collab = $pick('collaboration', 'bekerja bersama tim', 'mengerjakan tugas secara mandiri', 'berganti antara kerja mandiri dan tim');
        $comm   = $pick('communication', 'komunikasi langsung', 'komunikasi tertulis', 'komunikasi yang fleksibel');
        $pace   = $pick('workPace', 'ritme yang dinamis', 'ritme yang stabil dan terencana', 'ritme kerja yang adaptif');
        $env    = $pick('environment', 'aktivitas di lapangan', 'bekerja jarak jauh', 'bekerja jarak jauh maupun di lokasi');

        return "Kamu cenderung nyaman {$collab}, menyukai {$comm}, dan menikmati {$pace}. Kamu juga cocok untuk {$env}.";
    }

    /**
     * Label singkat posisi jawaban pada satu dimensi.
     */
    public static function dimensionLabel(array $question, int $value): string
    {
        return match (true) {
            $value <= 1 => 'Sangat ' . strtolower($question['low']),
            $value === 2 => 'Cenderung ' . strtolower($question['low']),
            $value === 3 => 'Seimbang',
            $value === 4 => 'Cenderung ' . strtolower($question['high']),
            default => 'Sangat ' . strtolower($question['high']),
        };
    }
}
