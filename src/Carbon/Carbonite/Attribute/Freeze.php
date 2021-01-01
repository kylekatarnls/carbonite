<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\Carbonite;

#[Attribute]
class Freeze extends AttributeBase // @codeCoverageIgnore
{
    public function up(): void
    {
        Carbonite::freeze(...$this->getArguments());
    }
}
