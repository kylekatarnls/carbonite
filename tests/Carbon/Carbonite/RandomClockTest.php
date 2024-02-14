<?php

declare(strict_types=1);

namespace Carbonite;

use Carbon\Carbonite\DataGroup;
use Carbon\Carbonite\RandomClock;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Carbon\Carbonite\RandomClock
 */
class RandomClockTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::between
     * @covers ::now
     */
    public function testBasicMatrix(): void
    {
        $clock = RandomClock::between('2024-02-01', '2024-03-01');

        for ($i = 0; $i < 1000; $i++) {
            $date = $clock->now()->format('Y-m-d');

            self::assertGreaterThanOrEqual('2024-02-01', $date);
            self::assertLessThanOrEqual('2024-03-01', $date);
        }
    }

    /**
     * @covers ::repeat
     */
    public function testRepeat(): void
    {
        $clock = RandomClock::between('2024-02-01', '2024-03-01');
        self::assertEquals(
            DataGroup::between('2024-02-01', '2024-03-01', [1, 2, 3]),
            $clock->repeat(3)
        );
    }
}
