<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ProjectApplyService::cancel() ketika pembatalan lamaran ditolak
 * (lamaran tidak ada, status tidak bisa dibatalkan, atau batas pembatalan mingguan habis).
 */
class CancelNotAllowedException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly int $httpStatus,
        public readonly array $cancelStatus = [],
    ) {
        parent::__construct($message);
    }
}
