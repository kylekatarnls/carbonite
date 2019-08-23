<?php

namespace Carbon\Carbonite;

use RuntimeException;
use Throwable;

final class UnfrozenTimeException extends RuntimeException
{
    public function __construct($message = 'The time is not currently frozen.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
