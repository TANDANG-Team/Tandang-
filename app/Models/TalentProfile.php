<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TalentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'institution',
        'major',
        'city',
        'bio',
        'skills',
        'availability',
        'work_mode',
        'document',
        'document_path',
        'document_original_name',
        'document_size',
        'eligibility_status',
        'verification_status',
        'rejection_reason',
        'screening_notes',
        'submitted_at',
        'verified_at',
        'verified_by',
        'class_level',
        'academic_year',
        'graduation_year',
        'entry_year',
        'current_semester',
        'graduation_month',
        'assessment_answers',
        'assessment_completed_at',
        'portfolios',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'assessment_answers' => 'array',
            'portfolios' => 'array',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'assessment_completed_at' => 'datetime',
            'document_size' => 'integer',
            'graduation_year' => 'integer',
            'entry_year' => 'integer',
            'current_semester' => 'integer',
            'graduation_month' => 'integer',
        ];
    }

    /**
     * Portofolio yang sudah dirapikan untuk ditampilkan:
     * memisahkan link karya dan gambar sampul, termasuk data lama
     * yang menyimpan link karya di kolom "image".
     *
     * @return array<int, array{id: string, title: string, description: string, link: ?string, image: ?string, domain: ?string}>
     */
    public static function presentPortfolios($items): array
    {
        $safe = fn ($u) => (is_string($u) && preg_match('#^https?://#i', trim($u))) ? trim($u) : null;

        return collect($items ?? [])
            ->map(fn ($p) => is_array($p) ? $p : ['title' => (string) $p])
            ->filter(fn ($p) => !empty($p['title']))
            ->map(function ($p) use ($safe) {
                $link = $safe($p['url'] ?? null);
                $image = $safe($p['image'] ?? null);

                // Data lama: link karya (Figma, Drive, Behance, dll.) tersimpan sebagai "image"
                if ($image && !self::looksLikeImage($image)) {
                    $link = $link ?: $image;
                    $image = null;
                }

                return [
                    'id' => (string) ($p['id'] ?? ''),
                    'title' => $p['title'],
                    'description' => $p['description'] ?? '',
                    'link' => $link,
                    'image' => $image,
                    'domain' => $link ? preg_replace('/^www\./i', '', (string) parse_url($link, PHP_URL_HOST)) : null,
                ];
            })
            ->values()
            ->all();
    }

    public static function looksLikeImage(string $url): bool
    {
        return (bool) (
            preg_match('/\.(jpe?g|png|gif|webp|avif|svg|bmp)(\?|#|$)/i', parse_url($url, PHP_URL_PATH) ?? '')
            || preg_match('#^https?://(images\.unsplash\.com|plus\.unsplash\.com|i\.imgur\.com|i\.ibb\.co|res\.cloudinary\.com|lh\d\.googleusercontent\.com|images\.pexels\.com|cdn\.dribbble\.com|mir-s3-cdn-cf\.behance\.net)/#i', $url)
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Label Status Verifikasi Bahasa Indonesia
     */
    public function getVerificationStatusLabelAttribute(): string
    {
        return match ($this->verification_status) {
            'verified', 'Terverifikasi' => 'Terverifikasi',
            'pending', 'Menunggu Verifikasi' => 'Menunggu Verifikasi',
            'rejected', 'Ditolak' => 'Ditolak',
            'expired' => 'Kedaluwarsa',
            default => 'Belum Mengajukan',
        };
    }

    /**
     * Label Status Kelayakan Bahasa Indonesia
     */
    public function getEligibilityStatusLabelAttribute(): string
    {
        return match ($this->eligibility_status) {
            'eligible' => 'Memenuhi Syarat (Eligible)',
            'ineligible' => 'Tidak Memenuhi Syarat',
            default => 'Belum Ditentukan',
        };
    }

    /**
     * Format Ukuran File Dokumen (KB / MB)
     */
    public function getDocumentSizeFormattedAttribute(): string
    {
        if (!$this->document_size) {
            return '-';
        }

        if ($this->document_size >= 1048576) {
            return number_format($this->document_size / 1048576, 2) . ' MB';
        }

        return number_format($this->document_size / 1024, 1) . ' KB';
    }

    /**
     * Cek apakah dokumen fisik tersimpan di storage lokal private
     */
    public function getHasPhysicalDocumentAttribute(): bool
    {
        return !empty($this->document_path) && Storage::disk('local')->exists($this->document_path);
    }

    /**
     * Hitung persentase kelengkapan profil talenta
     */
    public function getCompletenessAttribute(): int
    {
        $fields = [
            !empty($this->category),
            !empty($this->institution),
            !empty($this->major),
            !empty($this->city),
            !empty($this->bio),
            !empty($this->skills) && count($this->skills) > 0,
            !empty($this->availability),
            !empty($this->work_mode),
            !empty($this->assessment_answers),
            $this->verification_status === 'verified' || $this->has_physical_document,
        ];

        $completed = count(array_filter($fields));
        return (int) round(($completed / count($fields)) * 100);
    }
}
