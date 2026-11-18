<?php

return [
    /*
    | Metode pembayaran aktif:
    |  - manual_qris : QRIS statis merchant + konfirmasi Penyedia + verifikasi manual Admin (default)
    |  - gateway     : (belum diimplementasikan) QRIS dinamis via Midtrans/Xendit + webhook terverifikasi
    | Simulasi development terpisah dan tidak pernah dihitung sebagai pendapatan.
    */
    'driver' => env('PROMOTION_PAYMENT_DRIVER', 'manual_qris'),

    // Tombol "Hanya untuk Pengujian". Otomatis mati di production.
    'simulation_enabled' => env('PROMOTION_PAYMENT_SIMULATION', env('APP_ENV', 'production') !== 'production'),

    // Batas waktu checkout: 5 menit sejak transaksi dibuat (bukan sejak halaman dibuka).
    'payment_window_seconds' => (int) env('PROMOTION_PAYMENT_WINDOW_SECONDS', 300),

    /*
    | QRIS statis merchant. Isi dengan QRIS ASLI milik rekening penerima.
    | Nama merchant harus sama persis dengan yang muncul di aplikasi pembayaran saat QR dipindai.
    | Bila salah satu kosong, halaman pembayaran menampilkan peringatan dan pembayaran nyata tidak dibuka.
    */
    'qris' => [
        'image'         => env('QRIS_IMAGE', 'images/payment/qris-sponsor.png'), // relatif ke public/
        // Bawaan = QRIS milik tim (gambarnya ikut di repo), jadi tidak perlu diisi lagi di .env.
        // Isi QRIS_MERCHANT_NAME / QRIS_MERCHANT_NMID di .env hanya bila memakai QRIS lain.
        'merchant_name' => env('QRIS_MERCHANT_NAME') ?: 'AMRUL HIDAYAT, CANDI',
        'merchant_nmid' => env('QRIS_MERCHANT_NMID') ?: 'ID1026535586410',
    ],

    /*
    | Syarat Sponsored Project: rating usaha (nilai asli, tanpa pembulatan)     // Atribusi lamaran: berlaku jika jumlah penilaian valid
    | dari Talenta pada proyek yang Selesai. Membeli promosi tidak pernah memengaruhi rating.
    */
    'eligibility' => [
        'min_rating'  => 4.0,
        'min_reviews' => 3,
    ],

    // Atribusi lamaran: berlaku jika lamaran dikirim maksimal N jam setelah klik promosi.
    'attribution_hours' => 24,

    // Jumlah maksimal proyek promosi yang tampil bergantian di carousel banner (beranda, rekomendasi, cari).
    'banner_max' => (int) env('PROMOTION_BANNER_MAX', 5),

    // Impression dianggap sah bila ≥50% kartu terlihat selama ≥1 detik (diukur di browser).
    'impression_visible_ratio' => 0.5,
    'impression_min_ms' => 1000,

    // Klik dari pengunjung yang sama dalam jendela ini dihitung sekali.
    'click_dedup_seconds' => 30,

    // Identitas simulasi (payload uji, bukan merchant nyata).
    'merchant_name' => 'TANDANG DEV',
    'merchant_city' => 'BANDUNG',
];
