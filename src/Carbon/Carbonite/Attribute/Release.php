<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\Carbonite;

#[Attribute]
class Release implements UpInterface
{
    public function up(): void
    {
        Carbonite::release();
    }
}
