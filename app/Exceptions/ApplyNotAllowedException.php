<?php

namespace App\Exceptions;

use App\Services\ProjectApplyService;
use RuntimeException;

/**
 * Dilempar ProjectApplyService::apply() ketika aturan kuota/pendaftaran menolak lamaran.
 * Membawa hasil getApplyStatus() lengkap agar controller bisa menampilkan alasan & sisa slot.
 */
class ApplyNotAllowedException extends RuntimeException
{
    public function __construct(public readonly array $applyStatus)
    {
        parent::__construct($applyStatus['message'] ?? 'Lamaran tidak dapat diproses.');
    }

    public function reason(): ?string
    {
        return $this->applyStatus['reason'] ?? null;
    }

    public function httpStatus(): int
    {
        return ProjectApplyService::httpStatusFor($this->reason());
    }
}
