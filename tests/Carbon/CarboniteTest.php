<?php

declare(strict_types=1);

namespace Tests\Carbon;

use Carbon\Carbon;
use Carbon\Carbonite;
use Carbon\Carbonite\Tibanna;
use Carbon\Carbonite\UnfrozenTimeException;
use Carbon\FactoryImmutable;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(Carbonite::class)]
#[CoversClass(Tibanna::class)]
class CarboniteTest extends TestCase
{
    protected function setUp(): void
    {
        Carbonite::mock(null);
        Carbonite::release();
    }

    public function testMock(): void
    {
        Carbonite::mock(Carbon::parse('2000-01-01'));
        Carbonite::release();

        self::assertSame('2000-01-01 00:00:00', Carbon::now()->format('Y-m-d H:i:s'));

        Carbonite::mock('2012-11-05');
        Carbonite::release();

        self::assertSame('2012-11-05 00:00:00', Carbon::now()->format('Y-m-d H:i:s'));

        Carbonite::mock(function (): string {
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

    public function testFake(): void
    {
        $before = new DateTimeImmutable('now');
        $realNow = new DateTimeImmutable('now');
        $fakeBefore = Carbonite::fake($realNow);
        $after = new DateTimeImmutable('now');

        $totalTime = ((float) $after->format('U.u')) - ((float) $before->format('U.u'));
        $diff = ((float) $fakeBefore->format('U.u')) - ((float) $realNow->format('U.u'));

        self::assertLessThan($totalTime, abs($diff));

        $realNow = new DateTimeImmutable('now');
        $fakeNow = Carbonite::fake($realNow);
        $afterFake = new DateTimeImmutable('now');

        $totalTime = ((float) $afterFake->format('U.u')) - ((float) $before->format('U.u'));
        $betweenTime = ((float) $realNow->format('U.u')) - ((float) $after->format('U.u'));
        $diff = ((float) $fakeNow->format('U.u')) - ((float) $before->format('U.u'));

        self::assertLessThan($totalTime, $diff);
        self::assertGreaterThan($betweenTime, $diff);

        Carbonite::mock($realNow);
        Carbonite::freeze($realNow);
        $fakeNow = Carbonite::fake($realNow);

        self::assertSame($realNow->format('Y-m-d H:i:s.u'), $fakeNow->format('Y-m-d H:i:s.u'));

        Carbonite::mock(null);
    }

    public function testFreeze(): void
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
        Carbonite::mock(function () use (&$day): string {
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

    public function testSpeed(): void
    {
        $seconds = 0;

        Carbonite::mock(function () use (&$seconds): Carbon {
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

        // Retry thrice
        for ($i = 0; $i < 3; $i++) {
            Carbonite::mock(null);
            Carbonite::release();
            Carbonite::speed(400);
            $nextSecond = Carbon::now()->addSeconds(2);
            usleep(10 * 1000);

            $nextSecond = $nextSecond->format('Y-m-d H:i:s.u');
            $now = Carbon::now()->format('Y-m-d H:i:s.u');

            if ($nextSecond > $now) {
                break;
            }
        }

        self::assertGreaterThan($nextSecond, $now);
    }

    public function testAccelerate(): void
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

    public function testDecelerate(): void
    {
        Carbonite::speed(0.0);
        Carbonite::decelerate(50.0);

        self::assertSame(0.0, Carbonite::speed());

        Carbonite::speed(1);
        Carbonite::decelerate(50.0);

        self::assertEqualsWithDelta(1 / 50, Carbonite::speed(), 0.000000001);

        Carbonite::decelerate(0.1);

        self::assertEqualsWithDelta(1 / 5, Carbonite::speed(), 0.000000001);

        Carbonite::decelerate(2.0);

        self::assertEqualsWithDelta(1 / 10, Carbonite::speed(), 0.000000001);
    }

    public function testUnfreeze(): void
    {
        Carbonite::freeze();

        self::assertSame(0.0, Carbonite::speed());

        Carbonite::unfreeze();

        self::assertSame(1.0, Carbonite::speed());
    }

    public function testUnfreezeException(): void
    {
        self::expectException(UnfrozenTimeException::class);

        Carbonite::freeze();
        Carbonite::unfreeze();
        Carbonite::unfreeze();
    }

    public function testJumpTo(): void
    {
        Carbonite::speed(2.0);
        Carbonite::jumpTo('2019-08-24');

        self::assertSame('2019-08-24', Carbon::today()->format('Y-m-d'));
        self::assertSame(2.0, Carbonite::speed());

        Carbonite::jumpTo('next Monday', 3.0);

        self::assertSame('2019-08-26', Carbon::today()->format('Y-m-d'));
        self::assertSame(3.0, Carbonite::speed());
    }

    public function testElapse(): void
    {
        Carbonite::speed(2.0);
        Carbonite::jumpTo('2019-08-12');

        self::assertSame('2019-08-12', Carbon::today()->format('Y-m-d'));
        self::assertSame(2.0, Carbonite::speed());

        Carbonite::elapse('3 months and 4 days');

        self::assertSame('2019-11-16', Carbon::today()->format('Y-m-d'));
        self::assertSame(2.0, Carbonite::speed());

        Carbonite::elapse(new DateInterval('P1Y'), 3.0);

        self::assertSame('2020-11-16', Carbon::today()->format('Y-m-d'));
        self::assertSame(3.0, Carbonite::speed());

        $realSeconds = 0;
        Carbonite::mock(function () use (&$realSeconds): Carbon {
            return Carbon::parse('2019-08-01')->addSeconds($realSeconds);
        });
        Carbonite::release();
        Carbonite::speed(3.0);

        $realSeconds += 5;
        Carbonite::elapse(3);

        self::assertSame('2019-08-01 00:00:18', Carbon::now()->format('Y-m-d H:i:s'));
    }

    public function testRewind(): void
    {
        Carbonite::speed(2.0);
        Carbonite::jumpTo('2019-08-12');

        self::assertSame('2019-08-12', Carbon::today()->format('Y-m-d'));
        self::assertSame(2.0, Carbonite::speed());

        Carbonite::rewind('3 months and 4 days');

        self::assertSame('2019-05-08', Carbon::today()->format('Y-m-d'));
        self::assertSame(2.0, Carbonite::speed());

        Carbonite::rewind(new DateInterval('P1Y'), 3.0);

        self::assertSame('2018-05-08', Carbon::today()->format('Y-m-d'));
        self::assertSame(3.0, Carbonite::speed());

        $realSeconds = 0;
        Carbonite::mock(function () use (&$realSeconds): Carbon {
            return Carbon::parse('2019-08-01')->addSeconds($realSeconds);
        });
        Carbonite::release();
        Carbonite::speed(3.0);

        $realSeconds += 5;
        Carbonite::rewind(3);

        self::assertSame('2019-08-01 00:00:12', Carbon::now()->format('Y-m-d H:i:s'));
    }

    public function testRelease(): void
    {
        Carbonite::freeze('2019-08-24');

        self::assertTrue(Carbon::hasTestNow());
        self::assertSame(0.0, Carbonite::speed());

        Carbonite::release();

        self::assertFalse(Carbon::hasTestNow());
        self::assertSame(1.0, Carbonite::speed());
    }

    public function testDo(): void
    {
        [
            $speed,
            $date,
            $hasTestNow,
            $nestedDate,
            $dateAgain,
            $hasTestNowAgain,
        ] = Carbonite::do('2019-08-24', static function () {
            usleep(42);

            return [
                Carbonite::speed(),
                Carbon::now()->format('Y-m-d H:i:s.u'),
                Carbon::hasTestNow(),
                Carbonite::do('2020-05-12 12:34:46.173726', static function () {
                    return Carbon::now()->format('Y-m-d H:i:s.u');
                }),
                Carbon::now()->format('Y-m-d H:i:s.u'),
                Carbon::hasTestNow(),
            ];
        });

        self::assertTrue($hasTestNow);
        self::assertSame(0.0, $speed);
        self::assertSame('2019-08-24 00:00:00.000000', $date);
        self::assertSame('2020-05-12 12:34:46.173726', $nestedDate);
        self::assertTrue($hasTestNowAgain);
        self::assertSame('2019-08-24 00:00:00.000000', $dateAgain);

        self::assertFalse(Carbon::hasTestNow());
        self::assertSame(1.0, Carbonite::speed());
        self::assertLessThan(500, Carbon::now()->diffInMicroseconds(new DateTime()));
    }

    public function testDoWithError(): void
    {
        $date = null;
        $message = null;

        try {
            Carbonite::do('2019-08-24', static function () use (&$date) {
                $date = Carbon::now()->format('Y-m-d');

                throw new Exception('stop');
            });

            $date = 'erased';
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        self::assertSame('2019-08-24', $date);
        self::assertSame('stop', $message);
        self::assertLessThan(500, Carbon::now()->diffInMicroseconds(new DateTime()));
    }

    public function testDoNow(): void
    {
        [
            $speed,
            $date,
            $hasTestNow,
            $nestedDate,
            $dateAgain,
            $hasTestNowAgain,
        ] = Carbonite::doNow(static function () {
            Carbonite::elapse('32 minutes');

            return [
                Carbonite::speed(),
                Carbon::now(),
                Carbon::hasTestNow(),
                Carbonite::doNow(static function () {
                    Carbonite::elapse('5 hours');

                    return Carbon::now();
                }),
                Carbon::now(),
                Carbon::hasTestNow(),
            ];
        });

        self::assertTrue($hasTestNow);
        self::assertSame(0.0, $speed);
        self::assertSame(32.0, abs(round($date->floatDiffInMinutes(new DateTime()))));
        self::assertSame(32.0 + 5.0 * 60.0, abs(round($nestedDate->floatDiffInMinutes(new DateTime()))));
        self::assertTrue($hasTestNowAgain);
        self::assertSame(32.0, abs(round($dateAgain->floatDiffInMinutes(new DateTime()))));

        self::assertFalse(Carbon::hasTestNow());
        self::assertSame(1.0, Carbonite::speed());
        self::assertSame(0.0, round(Carbon::now()->floatDiffInSeconds(new DateTime()) / 3));
    }

    public function testTibannaAutoRegeneration(): void
    {
        $property = new ReflectionProperty(Carbonite::class, 'tibanna');
        $property->setAccessible(true);
        @$property->setValue(Carbonite::class, null);

        Carbonite::freeze('2022-03-15');
        self::assertSame('2022-03-15', Carbon::now()->format('Y-m-d'));
    }

    public function testSynchronizer(): void
    {
        $calls = 0;
        $callback = static function () use (&$calls) {
            $calls++;
        };

        Carbonite::freeze('2024-01-26 12:00');
        self::assertSame(0, $calls);
        Carbonite::addSynchronizer($callback);
        Carbonite::freeze('2024-01-26 12:00');
        self::assertSame(1, $calls);
        Carbonite::jumpTo('2024-01-26 12:00');
        self::assertSame(2, $calls);
        Carbonite::removeSynchronizer($callback);
        Carbonite::freeze('2024-01-26 12:00');
        self::assertSame(2, $calls);
    }

    public function testGetClock(): void
    {
        self::assertInstanceOf(FactoryImmutable::class, Carbonite::getClock());
    }
}
