<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\Carbonite;

#[Attribute]
class Speed implements UpInterface
{
    private ?float $speed;

    public function __construct(?float $speed = null)
    {
        $this->speed = $speed;
    }

    public function up(): void
    {
        Carbonite::speed($this->speed);
    }
}
