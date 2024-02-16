<?php

declare(strict_types=1);

namespace Tests\Carbon;

use Carbon\BespinTimeMocking;
use Carbon\Carbon;
use Carbon\Carbonite;
use Carbon\Carbonite\Attribute\Freeze;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class BasicTest extends TestCase
{
    use BespinTimeMocking;

    public function testSpeed(): void
    {
        self::assertSame(0.0, Carbonite::speed());
    }

    #[Freeze('2024-01-15 08:00')]
    public function testFreezeAttribute(): void
    {
        self::assertSame('2024-01-15 08:00', Carbon::now()->format('Y-m-d H:i'));
    }
}
