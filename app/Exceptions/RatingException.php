<?php

namespace App\Exceptions;

use RuntimeException;

/** Pelanggaran aturan rating/penyelesaian kerja (pesannya aman ditampilkan ke pengguna). */
class RatingException extends RuntimeException
{
}
