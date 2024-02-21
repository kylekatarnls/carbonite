<?php

declare(strict_types=1);

namespace Tests\Carbon;

use Carbon\Carbonite;
use Carbon\Carbonite\UnfrozenTimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnfrozenTimeException::class)]
class UnfrozenTimeExceptionTest extends TestCase
{
    public function testUnfreezeException(): void
    {
        self::expectException(UnfrozenTimeException::class);
        self::expectExceptionMessage('The time is not currently frozen.');

        Carbonite::freeze();
        Carbonite::unfreeze();
        Carbonite::unfreeze();
    }
}
