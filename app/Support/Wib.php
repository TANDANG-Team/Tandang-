<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Database menyimpan waktu dalam UTC (config app.timezone). Helper ini hanya
 * mengonversi untuk tampilan agar konsisten dalam WIB.
 */
class Wib
{
    public const TZ = 'Asia/Jakarta';

    private const MONTHS = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public static function format(?CarbonInterface $time, bool $withTime = true): string
    {
        if (! $time) {
            return '—';
        }

        $t = $time->copy()->setTimezone(self::TZ);
        $date = $t->day . ' ' . self::MONTHS[$t->month] . ' ' . $t->year;

        return $withTime ? $date . ', ' . $t->format('H.i') . ' WIB' : $date;
    }

    public static function date(?CarbonInterface $time): string
    {
        return self::format($time, false);
    }
}
