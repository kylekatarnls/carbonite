<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\Carbonite;

#[Attribute]
class Speed extends AttributeBase
{
    public function up(): void
    {
        Carbonite::speed(...$this->getArguments());
    }
}
