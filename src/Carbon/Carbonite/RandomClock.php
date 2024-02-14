<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\CarbonImmutable;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

/**
 * Clock that returns a random date every time ->now() is called and pick it between
 * the given $min and $max parameters.
 */
final class RandomClock implements ClockInterface
{
    private $min;
    private $diffInMicroSeconds;

    /**
     * @param DatePeriod|DateInterval|DateTimeInterface|string|null $min
     * @param DatePeriod|DateInterval|DateTimeInterface|string|null $max
     */
    public function __construct($min, $max)
    {
        $now = CarbonImmutable::now();
        $this->min = $now->carbonize($min);
        $this->diffInMicroSeconds = $this->min->diffInMicroseconds($now->carbonize($max));
    }

    /**
     * Creates a clock that returns a random date every time ->now() is called and pick it between
     * the given $min and $max parameters.
     *
     * @param DatePeriod|DateInterval|DateTimeInterface|string|null $min
     * @param DatePeriod|DateInterval|DateTimeInterface|string|null $max
     */
    public static function between($min, $max): self
    {
        return new self($min, $max);
    }

    /**
     * Return a random date every time ->now() is called and pick it between
     * the given $min and $max parameters of the clock.
     */
    public function now(): DateTimeImmutable
    {
        return $this->min->addMicroseconds(mt_rand(0, (int) $this->diffInMicroSeconds));
    }

    /**
     * Returns a DataGroup crossing current clock with a range from 1 to given count.
     * So this can be used to run a test $count times with each time a different
     * random date.
     */
    public function repeat(int $count): DataGroup
    {
        return DataGroup::for($this, range(1, $count));
    }
}
