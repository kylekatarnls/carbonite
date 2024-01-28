<?php

/*
 * This file simulates \Symfony\Component\Clock\Clock
 */

namespace Symfony\Component\Clock;

use Psr\Clock\ClockInterface as PsrClockInterface;

final class Clock implements ClockInterface
{
    private static $globalClock;
    private $clock;
    private $timezone;

    public function __construct(
        ?PsrClockInterface $clock = null,
        ?\DateTimeZone $timezone = null
    ) {
        $this->clock = $clock;
        $this->timezone = $timezone;
    }

    /**
     * Returns the current global clock.
     *
     * Note that you should prefer injecting a ClockInterface or using
     * ClockAwareTrait when possible instead of using this method.
     */
    public static function get(): ClockInterface
    {
        return self::$globalClock = (self::$globalClock ?? new NativeClock());
    }

    public static function set(PsrClockInterface $clock): void
    {
        self::$globalClock = $clock instanceof ClockInterface ? $clock : new self($clock);
    }

    public function now(): \DateTimeImmutable
    {
        $now = ($this->clock ?? self::get())->now();

        if (!$now instanceof DatePoint) {
            $now = DatePoint::createFromInterface($now);
        }

        return isset($this->timezone) ? $now->setTimezone($this->timezone) : $now;
    }

    public function sleep($seconds): void
    {
        $clock = $this->clock ?? self::get();

        if ($clock instanceof ClockInterface) {
            $clock->sleep($seconds);
        } else {
            (new NativeClock())->sleep($seconds);
        }
    }

    /**
     * @throws \DateInvalidTimeZoneException When $timezone is invalid
     */
    public function withTimeZone($timezone): Clock
    {
        if (\PHP_VERSION_ID >= 80300 && \is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        } elseif (\is_string($timezone)) {
            try {
                $timezone = new \DateTimeZone($timezone);
            } catch (\Exception $e) {
                throw new \DateInvalidTimeZoneException($e->getMessage(), $e->getCode(), $e);
            }
        }

        $clone = clone $this;
        $clone->timezone = $timezone;

        return $clone;
    }
}
