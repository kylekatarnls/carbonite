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

        $mock = Carbon::now();
        $real = new DateTimeImmutable('now');

        $mock = (float) $mock->format('U.u');
        $real = (float) $real->format('U.u');

        self::assertLessThan(1, abs($mock - $real));
    }

    /**
     * @covers ::fake
     * @covers \Carbon\Carbonite\Tibanna::fake
     */
    public function testFake()
    {
        $realNow = Carbon::instance(new DateTimeImmutable('now'));
        $fakeNow = Carbonite::fake($realNow);

        $mock = (float) $fakeNow->format('U.u');
        $real = (float) $realNow->format('U.u');

        self::assertGreaterThan($realNow->format('Y-m-d H:i:s.u'), $fakeNow->format('Y-m-d H:i:s.u'));
        self::assertLessThan(1, abs($mock - $real));

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

    /**
     * @covers ::speed
     * @covers \Carbon\Carbonite\Tibanna::speed
     */
    public function testSpeed()
    {
        $seconds = 0;

        Carbonite::mock(function () use (&$seconds) {
            return Carbon::parse('2019-08-01')->addSeconds($seconds);
        });
        Carbonite::release();

        self::assertSame(1.0, Carbonite::speed());

        Carbonite::speed(0.0);

        self::assertSame(0.0, Carbonite::speed());
        self::assertSame('2019-08-01 00:00:00', Carbon::now()->format('Y-m-d H:i:s'));

        $seconds++;

        self::assertSame('00:00:00', Carbon::now()->format('H:i:s'));

        $seconds += 3654;

        self::assertSame('00:00:00', Carbon::now()->format('H:i:s'));

        Carbonite::speed(1.0);

        $seconds++;

        self::assertSame(1.0, Carbonite::speed());
        self::assertSame('00:00:01', Carbon::now()->format('H:i:s'));

        $seconds += 3654;

        self::assertSame('01:00:55', Carbon::now()->format('H:i:s'));

        Carbonite::speed(0.1);

        $seconds += 100;

        self::assertSame(0.1, Carbonite::speed());
        self::assertSame('01:01:05', Carbon::now()->format('H:i:s'));

        Carbonite::speed(5.0);

        $seconds += 3;

        self::assertSame(5.0, Carbonite::speed());
        self::assertSame('01:01:20', Carbon::now()->format('H:i:s'));
    }

    /**
     * @covers ::accelerate
     * @covers \Carbon\Carbonite\Tibanna::accelerate
     */
    public function testAccelerate()
    {
        Carbonite::speed(0.0);
        Carbonite::accelerate(50.0);

        self::assertSame(0.0, Carbonite::speed());

        Carbonite::speed(1);
        Carbonite::accelerate(50.0);

        self::assertSame(50.0, Carbonite::speed());

        Carbonite::accelerate(0.1);

        self::assertSame(5.0, Carbonite::speed());

        Carbonite::accelerate(2.0);

        self::assertSame(10.0, Carbonite::speed());
    }

    /**
     * @covers ::decelerate
     * @covers \Carbon\Carbonite\Tibanna::decelerate
     */
    public function testDecelerate()
    {
        Carbonite::speed(0.0);
        Carbonite::decelerate(50.0);

        self::assertSame(0.0, Carbonite::speed());

        Carbonite::speed(1);
        Carbonite::decelerate(50.0);

        self::assertSame(1 / 50, Carbonite::speed());

        Carbonite::decelerate(0.1);

        self::assertSame(1 / 5, Carbonite::speed());

        Carbonite::decelerate(2.0);

        self::assertSame(1 / 10, Carbonite::speed());
    }
}