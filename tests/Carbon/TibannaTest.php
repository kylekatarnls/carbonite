<?php

namespace Tests\Carbon;

use Carbon\Carbon;
use Carbon\Carbonite\Tibanna;
use Carbon\FactoryImmutable;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use ReflectionMethod;
use Symfony\Component\Clock\DatePoint;

/**
 * @coversDefaultClass \Carbon\Carbonite\Tibanna
 */
class TibannaTest extends TestCase
{
    /**
     * @covers ::callStaticMethodIfAvailable
     */
    public function testCallStaticMethodIfAvailable(): void
    {
        $method = new ReflectionMethod(Tibanna::class, 'callStaticMethodIfAvailable');
        $method->setAccessible(true);

        $date = $method->invoke(new Tibanna(), Carbon::class, 'parse', ['2000-01-01 00:00:00']);

        self::assertSame('2000-01-01 00:00:00', $date->format('Y-m-d H:i:s'));

        $result = $method->invoke(new Tibanna(), DateTimeImmutable::class, 'doesNotExist');

        self::assertNull($result);
    }

    /**
     * @covers ::freeze
     */
    public function testDatePoint(): void
    {
        if (!class_exists(DatePoint::class)) {
            self::markTestSkipped('Requires Symfony >= 7');
        }

        $factory = new FactoryImmutable();

        if (!($factory instanceof ClockInterface)) {
            self::markTestSkipped('Requires Carbon 2.69.0');
        }

        $tibanna = new Tibanna();
        $tibanna->freeze('2000-01-01 00:00:00', 0.5);

        $date = new DatePoint();

        self::assertGreaterThan('2000-01-01 00:00:00.000001', $date->format('Y-m-d H:i:s.u'));
        self::assertLessThan('2000-01-01 00:00:00.010000', $date->format('Y-m-d H:i:s.u'));
    }

    /**
     * @covers ::addSynchronizer
     * @covers ::removeSynchronizer
     * @covers ::setTestNow
     */
    public function testSynchronizer(): void
    {
        $calls = 0;
        $callback = static function () use (&$calls) {
            $calls++;
        };
        $tibanna = new Tibanna();

        $tibanna->freeze('2024-01-26 12:00');
        self::assertSame(0, $calls);
        $tibanna->addSynchronizer($callback);
        $tibanna->freeze('2024-01-26 12:00');
        self::assertSame(1, $calls);
        $tibanna->jumpTo('2024-01-26 12:00');
        self::assertSame(2, $calls);
        $tibanna->removeSynchronizer($callback);
        $tibanna->freeze('2024-01-26 12:00');
        self::assertSame(2, $calls);
    }

    /**
     * @covers ::getClock
     * @covers ::getDefaultClock
     */
    public function testGetClock(): void
    {
        $tibanna = new Tibanna();
        self::assertInstanceOf(FactoryImmutable::class, $tibanna->getClock());
    }
}
