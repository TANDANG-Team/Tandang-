<?php

namespace App\Support;

/**
 * Ilustrasi sampul bawaan (SVG data-URI) untuk proyek yang belum punya foto.
 * Warna konsisten per kategori dan tetap dalam palet TANDANG (teal + aksen kuning).
 */
class ProjectCover
{
    private const PALETTES = [
        ['#0f9d8a', '#18b8a2', '#ffb547'],
        ['#0c7566', '#0fa48f', '#ffd27a'],
        ['#12867a', '#3cc4ae', '#ffc061'],
        ['#0b5e55', '#13a08d', '#ffe0a3'],
        ['#14907f', '#5fd3c0', '#ffb547'],
        ['#0e6f64', '#26b39e', '#ffcf73'],
    ];

    private const ICONS = [
        'design'    => '<path d="M14 54 46 22l8 8-32 32H14z" /><path d="m40 28 8 8" />',
        'code'      => '<path d="m24 24-12 14 12 14" /><path d="m48 24 12 14-12 14" /><path d="m40 18-8 40" />',
        'megaphone' => '<path d="M14 32v12h8l20 10V22L22 32z" /><path d="M50 30a8 8 0 0 1 0 16" />',
        'camera'    => '<rect x="12" y="22" width="48" height="34" rx="6" /><circle cx="36" cy="39" r="9" /><path d="M26 22l4-6h12l4 6" />',
        'pen'       => '<path d="M16 56h40" /><path d="M44 16l8 8-24 24H20v-8z" />',
        'calendar'  => '<rect x="14" y="18" width="44" height="38" rx="5" /><path d="M14 30h44M26 12v10M46 12v10" />',
        'cup'       => '<path d="M16 26h34v14a14 14 0 0 1-14 14h-6a14 14 0 0 1-14-14z" /><path d="M50 30h4a6 6 0 0 1 0 12h-4" />',
        'book'      => '<path d="M14 18h18a6 6 0 0 1 6 6v32a5 5 0 0 0-5-5H14z" /><path d="M58 18H44a6 6 0 0 0-6 6v32a5 5 0 0 1 5-5h15z" />',
        'cart'      => '<circle cx="28" cy="54" r="3" /><circle cx="48" cy="54" r="3" /><path d="M12 16h8l6 28h26l6-20H24" />',
        'briefcase' => '<rect x="12" y="24" width="48" height="30" rx="5" /><path d="M28 24v-6h16v6M12 36h48" />',
    ];

    public static function fallback(string $category, string $title = ''): string
    {
        $key     = strtolower($category);
        $palette = self::PALETTES[abs(crc32($key ?: $title)) % count(self::PALETTES)];
        $icon    = self::ICONS[self::iconFor($key)];
        [$dark, $light, $accent] = $palette;

        // Komposisi 16:7 (rasio kartu proyek) dengan isi di tengah: di rasio lain (modal, thumbnail,
        // banner Sponsored) yang terpotong hanya pinggiran latar, ikon tetap utuh dan tidak ter-zoom berlebihan.
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 280" preserveAspectRatio="xMidYMid slice">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$dark}"/><stop offset="1" stop-color="{$light}"/>
    </linearGradient>
  </defs>
  <rect width="640" height="280" fill="url(#g)"/>
  <circle cx="548" cy="44" r="96" fill="#ffffff" fill-opacity=".07"/>
  <circle cx="92" cy="246" r="80" fill="#ffffff" fill-opacity=".05"/>
  <circle cx="404" cy="86" r="5" fill="{$accent}"/>
  <circle cx="236" cy="198" r="3.5" fill="#ffffff" fill-opacity=".45"/>
  <rect x="268" y="88" width="104" height="104" rx="26" fill="#ffffff" fill-opacity=".14" stroke="#ffffff" stroke-opacity=".28" stroke-width="1.5"/>
  <g transform="translate(266 83) scale(1.5)" fill="none" stroke="#ffffff" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round">{$icon}</g>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private static function iconFor(string $category): string
    {
        return match (true) {
            str_contains($category, 'desain') || str_contains($category, 'kreatif') => 'design',
            str_contains($category, 'web') || str_contains($category, 'develop')    => 'code',
            str_contains($category, 'marketing') || str_contains($category, 'media') => 'megaphone',
            str_contains($category, 'foto') || str_contains($category, 'video')     => 'camera',
            str_contains($category, 'penulisan') || str_contains($category, 'copy') => 'pen',
            str_contains($category, 'event') || str_contains($category, 'acara')    => 'calendar',
            str_contains($category, 'kuliner')                                      => 'cup',
            str_contains($category, 'pendidikan') || str_contains($category, 'ajar') => 'book',
            str_contains($category, 'penjualan') || str_contains($category, 'pelanggan') => 'cart',
            default                                                                 => 'briefcase',
        };
    }
}
