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
        Carbonite::release();

        self::assertSame('2000-01-01 00:00:00', Carbon::now()->format('Y-m-d H:i:s'));

        Carbonite::mock('2012-11-05');
        Carbonite::release();

        self::assertSame('2012-11-05 00:00:00', Carbon::now()->format('Y-m-d H:i:s'));

        Carbonite::mock(function () {
            return '2012-11-05 20:02:20.020202';
        });
        Carbonite::release();

        self::assertSame('2012-11-05 20:02:20', Carbon::now()->format('Y-m-d H:i:s'));

        Carbonite::mock(null);
        Carbonite::release();

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

        self::assertSame($realNow->format('Y-m-d H:i:s.u'), $fakeNow->format('Y-m-d H:i:s.u'));

        Carbonite::mock(null);
    }

    /**
     * @covers ::freeze
     * @covers \Carbon\Carbonite\Tibanna::freeze
     */
    public function testFreeze()
    {
        Carbonite::mock('2019-08-24 10:25:12.110402');
        Carbonite::freeze('2042-06-25 03:50:34.665523');

        self::assertSame('2042-06-25 03:50:34.665523', Carbon::now()->format('Y-m-d H:i:s.u'));

        Carbonite::mock('2019-08-24 10:25:13.984562');

        self::assertSame('2042-06-25 03:50:34.665523', Carbon::now()->format('Y-m-d H:i:s.u'));

        Carbonite::mock('2019-08-24 10:25:13.984562');
        Carbonite::freeze('2034-02-01 02:24:46.265523', 1);

        self::assertSame('2034-02-01 02:24:46.265523', Carbon::now()->format('Y-m-d H:i:s.u'));

        Carbonite::mock('2019-08-24 10:25:14.084732');

        self::assertSame('2034-02-01 02:24:46.365693', Carbon::now()->format('Y-m-d H:i:s.u'));

        Carbonite::mock('2019-08-24 10:00:00');
        Carbonite::freeze('2034-02-01 02:24:46.265523', 3);
        Carbonite::mock('2019-08-25 23:00:00');

        self::assertSame('2034-02-05 17:24:46.265523', Carbon::now()->format('Y-m-d H:i:s.u'));

        $day = 1;
        Carbonite::mock(function () use (&$day) {
            return '2019-08-'.$day;
        });
        Carbonite::freeze('1789-07-14', 3);

        self::assertSame('1789-07-14', Carbon::now()->format('Y-m-d'));

        $day++;

        self::assertSame('1789-07-17', Carbon::now()->format('Y-m-d'));

        $day += 2;

        self::assertSame('1789-07-23', Carbon::now()->format('Y-m-d'));

        $day--;

        self::assertSame('1789-07-20', Carbon::now()->format('Y-m-d'));
    }
}
