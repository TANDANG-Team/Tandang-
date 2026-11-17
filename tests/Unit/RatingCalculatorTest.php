<?php

namespace Tests\Unit;

use App\Services\Rating\RatingCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RatingCalculatorTest extends TestCase
{
    private array $talentAspects = [
        'quality'        => ['label' => 'Kualitas', 'weight' => 30],
        'timeliness'     => ['label' => 'Waktu', 'weight' => 25],
        'communication'  => ['label' => 'Komunikasi', 'weight' => 20],
        'responsibility' => ['label' => 'Tanggung jawab', 'weight' => 15],
        'teamwork'       => ['label' => 'Kerja sama', 'weight' => 10],
    ];

    /**
     * Contoh di spesifikasi: 5,4,5,4,5. Spesifikasi menulis 4,55 tetapi perhitungannya
     * 1,5 + 1,0 + 1,0 + 0,6 + 0,5 = 4,60.
     */
    public function test_weighted_example_from_spec(): void
    {
        $score = (new RatingCalculator())->weightedScore($this->talentAspects, [
            'quality' => 5, 'timeliness' => 4, 'communication' => 5, 'responsibility' => 4, 'teamwork' => 5,
        ]);

        $this->assertSame(4.6, $score);
    }

    public function test_not_applicable_aspect_is_normalised(): void
    {
        $aspects = [
            'accuracy'        => ['label' => 'Kesesuaian', 'weight' => 30],
            'payment'         => ['label' => 'Pembayaran', 'weight' => 25, 'nullable' => true],
            'professionalism' => ['label' => 'Profesional', 'weight' => 20],
            'clarity'         => ['label' => 'Kejelasan', 'weight' => 15],
            'environment'     => ['label' => 'Lingkungan', 'weight' => 10],
        ];

        // Tanpa pembayaran: (5×30 + 4×20 + 4×15 + 3×10) / 75 = 320 / 75 = 4.27
        $score = (new RatingCalculator())->weightedScore($aspects, [
            'accuracy' => 5, 'payment' => null, 'professionalism' => 4, 'clarity' => 4, 'environment' => 3,
        ]);

        $this->assertSame(4.27, $score);
    }

    public function test_required_aspect_cannot_be_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RatingCalculator())->weightedScore($this->talentAspects, ['quality' => 5]);
    }

    public function test_score_out_of_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RatingCalculator())->weightedScore($this->talentAspects, [
            'quality' => 6, 'timeliness' => 4, 'communication' => 5, 'responsibility' => 4, 'teamwork' => 5,
        ]);
    }

    public function test_labels(): void
    {
        $this->assertSame('Belum ada penilaian', RatingCalculator::label(null));
        $this->assertSame('Sangat Baik', RatingCalculator::label(4.8));
        $this->assertSame('Baik', RatingCalculator::label(4.0));
    }
}
