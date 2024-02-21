<?php

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\Carbonite;

#[Attribute]
readonly class Speed implements UpInterface
{
    public function __construct(private ?float $speed = null)
    {
    }

    public function up(): void
    {
        Carbonite::speed($this->speed);
    }
}
