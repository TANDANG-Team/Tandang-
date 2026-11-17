<?php

use App\Services\PromotionService;
use App\Services\Rating\RatingService;
use App\Services\Rating\WorkCompletionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sponsored Project: tagihan kedaluwarsa, promosi berakhir, dan promosi yang proyeknya tidak lagi layak.
Artisan::command('promotions:maintain', function (PromotionService $service) {
    $r = $service->runMaintenance();
    $this->info("Tagihan kedaluwarsa: {$r['expired_transactions']} · Promosi berakhir: {$r['ended']} · Dihentikan otomatis: {$r['stopped']}");
})->purpose('Perbarui status transaksi & promosi Sponsored Project');

// Rating: konfirmasi selesai otomatis & publikasi rating yang periode penilaiannya habis.
Artisan::command('ratings:maintain', function (WorkCompletionService $work, RatingService $ratings) {
    $auto = $work->autoConfirmStale();
    $published = $ratings->publishExpiredWindows();
    $this->info("Selesai otomatis: {$auto} · Rating dipublikasikan: {$published}");
})->purpose('Perbarui status pengerjaan & publikasi rating');

Schedule::command('promotions:maintain')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('ratings:maintain')->hourly()->withoutOverlapping();
