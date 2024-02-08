<?php

declare(strict_types=1);

namespace Tests\Carbon;

use Carbon\BespinTimeMocking;
use Carbon\Carbon;
use Carbon\Carbonite;
use Carbon\Carbonite\Attribute\Freeze;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    use BespinTimeMocking;

    /** @coversNothing */
    public function testSpeed(): void
    {
        self::assertSame(0.0, Carbonite::speed());
    }

    /** @coversNothing */
    #[Freeze('2024-01-15 08:00')]
    public function testFreeze(): void
    {
        self::assertSame('2024-01-15 08:00', Carbon::now()->format('Y-m-d H:i'));
    }
}
