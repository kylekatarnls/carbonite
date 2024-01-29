<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use RuntimeException;
use Throwable;

final class UnfrozenTimeException extends RuntimeException
{
    public function __construct(
        string $message = 'The time is not currently frozen.',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
