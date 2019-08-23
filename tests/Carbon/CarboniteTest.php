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
    protected function setUp(): void
    {
        parent::setUp();

        Carbonite::mock(null);
        Carbonite::release();
    }

    /**
     * @covers ::mock
     * @covers ::tibanna
     * @covers \Carbon\Carbonite\Tibanna::mock
     */
    public function testMock()
    {
        Carbonite::mock(Carbon::parse('2000-01-01'));

        self::assertSame('2000-01-01 00:00:00', Carbon::now()->format('Y-m-d H:i:s'));

        Carbonite::mock(null);

        self::assertLessThan(1, abs(Carbon::now()->format('U.u') - (new DateTimeImmutable('now'))->format('U.u')));
    }

    /**
     * @covers ::fake
     * @covers \Carbon\Carbonite\Tibanna::fake
     */
    public function testFake()
    {
        $realNow = Carbon::instance(new DateTimeImmutable('now'));
        $fakeNow = Carbonite::fake($realNow);

        self::assertGreaterThan($realNow->format('Y-m-d H:i:s.u'), $fakeNow->format('Y-m-d H:i:s.u'));
        self::assertLessThan(1, abs($fakeNow->format('U.u') - $realNow->format('U.u')));

        Carbonite::mock($realNow);
        Carbonite::freeze($realNow);
        $fakeNow = Carbonite::fake($realNow);

        self::assertSame($realNow->format('Y-m-d H:i:s.u'), Carbon::now()->format('Y-m-d H:i:s.u'));

        Carbonite::mock(null);
    }
}
