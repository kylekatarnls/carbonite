<?php

namespace Carbon;

use Carbon\Carbonite\Tibanna;
use Carbon\Carbonite\UnfrozenTimeException;
use Closure;
use DateInterval;
use DatePeriod;
use DateTimeInterface;

class Carbonite
{
    /**
     * The Tibanna instance is our singleton instance because any action with the Carbonite
     * needs Tibanna gas.
     *
     * @var Tibanna|null
     */
    private static $tibanna = null;

    /**
     * The Tibanna instance is our singleton instance because any action with the Carbonite
     * needs Tibanna gas.
     *
     * @return Tibanna
     */
    private static function tibanna(): Tibanna
    {
        self::$tibanna = null;

        if (self::$tibanna === null) {
            self::$tibanna = new Tibanna();
        }

        return self::$tibanna;
    }

    /**
     * Get fake now instance from real now instance.
     *
     * @param CarbonInterface $realNow
     *
     * @return Carbon|CarbonImmutable
     */
    public static function fake(CarbonInterface $realNow)
    {
        return self::tibanna()->fake($realNow);
    }

    /**
     * Freeze the time to a given moment (now by default).
     * As a second optional parameter you can choose the new time speed after the freeze (0 by default).
     *
     * @param string|Carbon|CarbonImmutable|CarbonPeriod|CarbonInterval|DateTimeInterface|DatePeriod|DateInterval $toMoment
     * @param float                                                                                               $speed
     */
    public static function freeze($toMoment = 'now', float $speed = 0): void
    {
        self::tibanna()->freeze($toMoment, $speed);
    }

    /**
     * Set the speed factor of the fake timeline and return the new speed.
     * If $speed is null, it just returns the current speed.
     *  - 0 = Frozen time
     *  - 0.5 = Time passes twice more slowly
     *  - 1 = Real life speed
     *  - 2 = Time passes twice faster
     *
     * @param float|null $speed
     *
     * @return float
     */
    public static function speed(float $speed = null): float
    {
        return self::tibanna()->speed($speed);
    }

    /**
     * Speed up the time in the fake timeline by the given factor.
     * Returns the new speed.
     *
     * @param float|null $factor
     *
     * @return float
     */
    public static function accelerate(float $factor): float
    {
        return self::tibanna()->accelerate($factor);
    }

    /**
     * Slow down the time in the fake timeline by the given factor.
     * Returns the new speed.
     *
     * @param float|null $factor
     *
     * @return float
     */
    public static function decelerate(float $factor): float
    {
        return self::tibanna()->decelerate($factor);
    }

    /**
     * Unfreeze the fake timeline.
     *
     * @throws UnfrozenTimeException if time was not frozen when called.
     */
    public static function unfreeze(): void
    {
        self::tibanna()->unfreeze();
    }

    /**
     * Jump to a given moment in the fake timeline keeping the current speed.
     * A second parameter can be passed to change the speed after the jump.
     *
     * @param string|Carbon|CarbonImmutable|CarbonPeriod|CarbonInterval|DateTimeInterface|DatePeriod|DateInterval $moment
     * @param float                                                                                               $speed
     */
    public static function jumpTo($moment, float $speed = null): void
    {
        self::tibanna()->jumpTo($moment, $speed);
    }

    /**
     * Add the given duration to the fake timeline keeping the current speed.
     * A second parameter can be passed to change the speed after the jump.
     * The duration can be a string like "3 days and 4 hours" a number of second (can be decimal)
     * or a interval (DateInterval/CarbonInterval).
     *
     * @param string|float|CarbonInterval|DateInterval $duration
     * @param float                                    $speed
     */
    public static function elapse($duration, float $speed = null): void
    {
        self::tibanna()->elapse($duration, $speed);
    }

    /**
     * Add the given duration to the fake timeline keeping the current speed.
     * A second parameter can be passed to change the speed after the jump.
     * The duration can be a string like "3 days and 4 hours" a number of second (can be decimal)
     * or a interval (DateInterval/CarbonInterval).
     *
     * @param string|float|CarbonInterval|DateInterval $duration
     * @param float                                    $speed
     */
    public static function rewind($duration, float $speed = null): void
    {
        self::tibanna()->rewind($duration, $speed);
    }

    /**
     * Go back to the present and normal speed.
     */
    public static function release(): void
    {
        self::tibanna()->release();
    }

    /**
     * Set the "real" now moment, it's a mock inception. It means that when you call release()
     * You will no longer go back to present but you will fallback to the mocked now. And the
     * mocked now will also determine the base speed to consider. If this mocked instance is
     * static, then "real" time will be frozen and so the fake timeline too no matter the speed
     * you chose.
     *
     * This is a very low-level feature used for the internal unit tests of Carbonite and you
     * probably won't need this methods in your own code and tests, you more likely need the
     * freeze() or jumpTo() method.
     *
     * @param CarbonInterface|Closure $testNow
     */
    public static function mock($testNow): void
    {
        self::tibanna()->mock($testNow);
    }
}
