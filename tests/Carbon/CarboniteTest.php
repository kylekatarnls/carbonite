<?php

namespace Tests\Carbon;

use Carbon\Carbon;
use Carbon\Carbonite;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Carbon\Carbonite
 */
class CarboniteTest extends TestCase
{
    /**
     * @covers ::mock
     * @covers \Carbon\Carbonite\Tibanna::mock
     */
    public function testMock()
    {
        Carbonite::mock(Carbon::parse('2000-01-01'));

        self::assertSame('2000-01-01 00:00:00', Carbon::now()->format('Y-m-d H:i:s'));

        Carbonite::mock(null);

        self::assertLessThan(1, abs(Carbon::now()->format('U.u') - (new DateTimeImmutable('now'))->format('U.u')));
    }
}
