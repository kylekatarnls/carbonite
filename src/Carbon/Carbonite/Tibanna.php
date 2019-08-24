<?php

namespace Carbon\Carbonite;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Closure;
use DateInterval;
use DatePeriod;
use DateTimeInterface;

class Tibanna
{
    /**
     * Current base moment of the fake timeline.
     *
     * @var CarbonInterface|null
     */
    private $moment = null;

    /**
     * Last real moment when the time speed changed.
     *
     * @var CarbonInterface|null
     */
    private $lastFrozenAt = null;

    /**
     * Speed of the fake timeline.
     *
     * @var float
     */
    private $speed = 1.0;

    /**
     * The mocked now instance to test Carbonite itself with fake time.
     * Because nothing is real.
     *
     * @var Closure|CarbonInterface|null
     */
    private $testNow = null;

    /**
     * Get fake now instance from real now instance.
     *
     * @param CarbonInterface $realNow
     *
     * @return CarbonInterface
     */
    public function fake(CarbonInterface $realNow): CarbonInterface
    {
        if (!$this->moment) {
            $this->speed(1.0);
        }

        /** @var CarbonInterface $moment */
        $moment = $this->moment;
        $fakeNow = $moment->copy();

        if (!$this->speed) {
            return $fakeNow;
        }

        $microseconds = $realNow->diffInMicroseconds($this->lastFrozenAt, true);

        return $fakeNow->addMicroseconds((int) round($microseconds * $this->speed));
    }

    /**
     * Freeze the time to a given moment (now by default).
     * As a second optional parameter you can choose the new time speed after the freeze (0 by default).
     *
     * @param string|CarbonInterface|CarbonPeriod|CarbonInterval|DateTimeInterface|DatePeriod|DateInterval $toMoment
     * @param float                                                                                        $speed
     */
    public function freeze($toMoment = 'now', float $speed = 0.0): void
    {
        $this->moment = Carbon::now()->carbonize($toMoment);
        Carbon::setTestNow($this->testNow);
        $this->lastFrozenAt = Carbon::now();

        $getNow = function (CarbonInterface $realNow) {
            $testNow = $this->testNow;

            if ($testNow) {
                if ($testNow instanceof Closure) {
                    $testNow = $testNow($realNow);
                }

                $realNow = $testNow;
            }

            return $this->fake($realNow);
        };

        Carbon::setTestNow($getNow);
        CarbonImmutable::setTestNow($getNow);
        $this->speed = $speed;
    }

    /**
     * Set the speed factor of the fake timeline and return the new speed.
     * If $speed is null, it just returns the current speed.
     *  - 0 = Frozen time;
     *  - 0.5 = Time passes twice more slowly;
     *  - 1 = Real life speed;
     *  - 2 = Time passes twice faster.
     *
     * @param float|null $speed
     *
     * @return float
     */
    public function speed(float $speed = null): float
    {
        if ($speed !== null) {
            $this->freeze(Carbon::now(), $speed);
        }

        return $this->speed;
    }

    /**
     * Speed up the time in the fake timeline by the given factor.
     * Returns the new speed.
     *
     * @param float $factor
     *
     * @return float
     */
    public function accelerate(float $factor): float
    {
        return $this->speed($this->speed * $factor);
    }

    /**
     * Slow down the time in the fake timeline by the given factor.
     * Returns the new speed.
     *
     * @param float $factor
     *
     * @return float
     */
    public function decelerate(float $factor): float
    {
        return $this->speed($this->speed / $factor);
    }

    /**
     * Unfreeze the fake timeline.
     *
     * @throws UnfrozenTimeException if time was not frozen when called.
     */
    public function unfreeze(): void
    {
        if ($this->speed !== 0.0) {
            throw new UnfrozenTimeException();
        }

        $this->speed(1.0);
    }

    /**
     * Jump to a given moment in the fake timeline keeping the current speed.
     * A second parameter can be passed to change the speed after the jump.
     *
     * @param string|CarbonInterface|CarbonPeriod|CarbonInterval|DateTimeInterface|DatePeriod|DateInterval $moment
     * @param float                                                                                        $speed
     */
    public function jumpTo($moment, float $speed = null): void
    {
        $this->freeze($moment, $speed === null ? $this->speed : $speed);
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
    public function elapse($duration, float $speed = null): void
    {
        $this->callDurationMethodAndJump('add', $duration, $speed);
    }

    /**
     * Add the given duration to the fake timeline keeping the current speed.
     * A second parameter can be passed to change the speed after the jump.
     * The duration can be a string like "3 days and 4 hours" a number of second (can be decimal)
     * or a interval (DateInterval/CarbonInterval).
     *
     * @param string|int|float|CarbonInterval|DateInterval $duration
     * @param float                                        $speed
     */
    public function rewind($duration, float $speed = null): void
    {
        $this->callDurationMethodAndJump('sub', $duration, $speed);
    }

    /**
     * Go back to the present and normal speed.
     */
    public function release(): void
    {
        $this->moment = null;
        $this->speed = 1.0;
        Carbon::setTestNow($this->testNow);
        CarbonImmutable::setTestNow($this->testNow);
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
     * @param string|CarbonInterface|Closure|null $testNow
     */
    public function mock($testNow): void
    {
        if ($testNow instanceof Closure) {
            $this->testNow = function (CarbonInterface $realNow) use ($testNow) {
                $fakeNow = $testNow($realNow);

                return $fakeNow instanceof CarbonInterface ? $fakeNow : Carbon::make($fakeNow);
            };

            return;
        }

        $this->testNow = $testNow instanceof CarbonInterface ? $testNow : Carbon::make($testNow);
    }

    /**
     * Call a duration method and jump to resulted date.
     *
     * @param string                                       $method
     * @param string|int|float|CarbonInterval|DateInterval $duration
     * @param float                                        $speed
     */
    private function callDurationMethodAndJump(string $method, $duration, float $speed = null): void
    {
        if (is_int($duration) || is_float($duration)) {
            $duration = "$duration seconds";
        }

        $this->jumpTo(Carbon::now()->$method($duration), $speed);
    }
}
