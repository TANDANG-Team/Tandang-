<?php

return [
    // Jendela penilaian sejak pekerjaan dikonfirmasi selesai.
    'window_days' => 7,

    // Jika pihak lain tidak merespons pengajuan selesai dalam N hari, dianggap selesai otomatis.
    'auto_confirm_days' => 7,

    // Penyedia → Talenta (bobot dalam persen)
    'talent_aspects' => [
        'quality'        => ['label' => 'Kualitas pekerjaan', 'weight' => 30],
        'timeliness'     => ['label' => 'Ketepatan waktu', 'weight' => 25],
        'communication'  => ['label' => 'Komunikasi', 'weight' => 20],
        'responsibility' => ['label' => 'Tanggung jawab', 'weight' => 15],
        'teamwork'       => ['label' => 'Kerja sama', 'weight' => 10],
    ],

    // Talenta → Penyedia. 'payment' menjadi N/A untuk proyek tanpa kompensasi finansial.
    'provider_aspects' => [
        'accuracy'        => ['label' => 'Kesesuaian deskripsi dengan pekerjaan aktual', 'weight' => 30],
        'payment'         => ['label' => 'Ketepatan pembayaran/kompensasi', 'weight' => 25, 'nullable' => true],
        'professionalism' => ['label' => 'Komunikasi dan profesionalisme', 'weight' => 20],
        'clarity'         => ['label' => 'Kejelasan instruksi pekerjaan', 'weight' => 15],
        'environment'     => ['label' => 'Lingkungan dan pengalaman kerja', 'weight' => 10],
    ],
];
