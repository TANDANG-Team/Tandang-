<?php

namespace App\Services\Rating;

use InvalidArgumentException;

/**
 * Perhitungan nilai akhir rata-rata tertimbang.
 * Aspek bernilai null (N/A) dikeluarkan dan bobot sisanya dinormalisasi proporsional.
 */
class RatingCalculator
{
    /**
     * @param array<string, array{label:string, weight:int, nullable?:bool}> $aspects
     * @param array<string, int|null> $scores
     */
    public function weightedScore(array $aspects, array $scores): float
    {
        $sum = 0.0;
        $weightTotal = 0;

        foreach ($aspects as $key => $aspect) {
            $value = $scores[$key] ?? null;

            if ($value === null) {
                if (empty($aspect['nullable'])) {
                    throw new InvalidArgumentException("Aspek {$aspect['label']} wajib dinilai.");
                }
                continue; // N/A
            }

            if (! is_int($value) || $value < 1 || $value > 5) {
                throw new InvalidArgumentException("Nilai {$aspect['label']} harus 1–5.");
            }

            $sum += $value * $aspect['weight'];
            $weightTotal += $aspect['weight'];
        }

        if ($weightTotal === 0) {
            throw new InvalidArgumentException('Tidak ada aspek yang dinilai.');
        }

        return round($sum / $weightTotal, 2);
    }

    public static function label(?float $score): string
    {
        return match (true) {
            $score === null => 'Belum ada penilaian',
            $score >= 4.5   => 'Sangat Baik',
            $score >= 3.5   => 'Baik',
            $score >= 2.5   => 'Cukup',
            $score >= 1.5   => 'Kurang',
            default         => 'Sangat Kurang',
        };
    }
}
