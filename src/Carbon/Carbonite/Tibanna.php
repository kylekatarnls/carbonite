<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\FactoryImmutable;
use Closure;
use DateInterval;
use DatePeriod;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;

final class Tibanna
{
    /**
     * Current base moment of the fake timeline.
     */
    private ?CarbonInterface $moment = null;

    /**
     * Last real moment when the time speed changed.
     */
    private ?CarbonInterface $lastFrozenAt = null;

    /**
     * Speed of the fake timeline.
     */
    private float $speed = 1.0;

    /**
     * The mocked now instance to test Carbonite itself with fake time.
     * Because nothing is real.
     */
    private Closure|CarbonInterface|null $testNow = null;

    /**
     * List of callbacks to execute when changing mocked date.
     *
     * @var callable[]
     */
    private array $synchronizers = [];

    /**
     * Get fake now instance from real now instance.
     */
    public function fake(DateTimeInterface $realNow): CarbonInterface
    {
        if (!$this->moment) {
            $this->speed(1.0);
        }

        // Calling speed() if $this->moment is null implies that both moment and lastFrozenAt are set:
        /** @var CarbonInterface $moment */
        $moment = $this->moment;
        /** @var CarbonInterface $lastFrozenAt */
        $lastFrozenAt = $this->lastFrozenAt;

        $fakeNow = $moment->copy();

        if (!$this->speed) {
            return $fakeNow;
        }

        $microseconds = $lastFrozenAt->diffInMicroseconds($realNow, false);

        return $fakeNow->addMicroseconds((int) round($microseconds * $this->speed));
    }

    /**
     * Freeze the time to a given moment (now by default).
     * As a second optional parameter you can choose the new time speed after the freeze (0 by default).
     */
    public function freeze(
        string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $toMoment = 'now',
        float $speed = 0.0,
    ): void {
        static $setTestNow = null;

        // @codeCoverageIgnoreStart
        if ($setTestNow === null) {
            $setTestNow = [
                Carbon::class,
                method_exists(Carbon::class, 'setTestNowAndTimezone') ? 'setTestNowAndTimezone' : 'setTestNow',
            ];
        }
        // @codeCoverageIgnoreEnd

        $this->moment = Carbon::now()->carbonize($toMoment instanceof ClockInterface ? $toMoment->now() : $toMoment);
        $setTestNow($this->testNow);
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

        $this->setTestNow($getNow);
        $this->speed = $speed;
    }

    /**
     * Set the speed factor of the fake timeline and return the new speed.
     * If $speed is null, it just returns the current speed.
     *  - 0 = Frozen time;
     *  - 0.5 = Time passes twice more slowly;
     *  - 1 = Real life speed;
     *  - 2 = Time passes twice as fast.
     */
    public function speed(?float $speed = null): float
    {
        if ($speed !== null) {
            $this->freeze('now', $speed);
        }

        return $this->speed;
    }

    /**
     * Speed up the time in the fake timeline by the given factor.
     * Returns the new speed.
     */
    public function accelerate(float $factor): float
    {
        return $this->speed($this->speed * $factor);
    }

    /**
     * Slow down the time in the fake timeline by the given factor.
     * Returns the new speed.
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
     */
    public function jumpTo(
        string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $moment,
        ?float $speed = null,
    ): void {
        $this->freeze($moment, $speed === null ? $this->speed : $speed);
    }

    /**
     * Add the given duration to the fake timeline keeping the current speed.
     * A second parameter can be passed to change the speed after the jump.
     * The duration can be a string like "3 days and 4 hours" a number of second (can be decimal)
     * or an interval (DateInterval/CarbonInterval).
     */
    public function elapse(string|int|float|DateInterval $duration, ?float $speed = null): void
    {
        $this->callDurationMethodAndJump('add', $duration, $speed);
    }

    /**
     * Subtract the given duration to the fake timeline keeping the current speed.
     * A second parameter can be passed to change the speed after the jump.
     * The duration can be a string like "3 days and 4 hours" a number of second (can be decimal)
     * or an interval (DateInterval/CarbonInterval).
     */
    public function rewind(string|int|float|DateInterval $duration, ?float $speed = null): void
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
        $this->setTestNow($this->testNow);
    }

    /**
     * Set the "real" now moment, it's a mock inception. It means that when you call release()
     * You will no longer go back to present, but you will fall back to the mocked now. And the
     * mocked now will also determine the base speed to consider. If this mocked instance is
     * static, then "real" time will be frozen and so the fake timeline too, no matter the speed
     * you chose.
     *
     * This is a very low-level feature used for the internal unit tests of Carbonite and you
     * probably won't need this method in your own code and tests, you more likely need the
     * freeze() or jumpTo() method.
     */
    public function mock(string|DateTimeInterface|Closure|null $testNow): void
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
     * Trigger a given $action in a frozen instant $testNow. And restore previous moment and
     * speed once it's done, rather it succeeded or threw an error or an exception.
     *
     * Returns the value returned by the given $action.
     */
    public function do(
        string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $testNow,
        callable $action,
    ): mixed {
        $clock = self::callIfAvailable([Clock::class, 'get']);
        $initialSpeed = $this->speed;
        $factory = $this->getClock();
        $initialFactoryTestNow = $factory->getTestNow();
        $initialTestNow = $this->testNow;
        $initialMoment = $this->moment;
        $initialFrozenAt = $this->lastFrozenAt;
        $this->freeze($testNow, 0);

        try {
            return $action();
        } finally {
            $this->speed = $initialSpeed;
            $this->testNow = $initialTestNow;
            $this->moment = $initialMoment;
            $this->lastFrozenAt = $initialFrozenAt;
            $factory->setTestNow($initialFactoryTestNow);
            self::callIfAvailable([Clock::class, 'set'], [$clock]);
        }
    }

    /**
     * Register a callback that will be executed every time mock value is changed.
     *
     * The callback receives the default \Carbon\FactoryImmutable as parameter.
     */
    public function addSynchronizer(callable $synchronizer): void
    {
        $this->synchronizers[] = $synchronizer;
    }

    /**
     * Remove a callback that has been registered with addSynchronizer().
     */
    public function removeSynchronizer(callable $synchronizer): void
    {
        $this->synchronizers = array_filter(
            $this->synchronizers,
            static function (callable $value) use ($synchronizer): bool {
                return $value !== $synchronizer;
            }
        );
    }

    /**
     * Return the default \Carbon\FactoryImmutable instance.
     *
     * @suppress PhanAccessMethodInternal
     */
    public function getClock(): FactoryImmutable
    {
        return FactoryImmutable::getDefaultInstance();
    }

    /**
     * Set a Carbon and CarbonImmutable instance (real or mock) to be returned when a "now" instance
     * is created. The provided instance will be returned specifically under the following conditions:
     *   - A call to the static now() method, ex. Carbon::now()
     *   - When a null (or blank string) is passed to the constructor or parse(), ex. new Carbon(null)
     *   - When the string "now" is passed to the constructor or parse(), ex. new Carbon('now')
     *   - When a string containing the desired time is passed to Carbon::parse().
     *
     * Note the timezone parameter was left out of the examples above and has no affect as the mock
     * value will be returned regardless of its value.
     *
     * To clear the test instance call this method using the default parameter of null.
     *
     * /!\ Use this method for unit tests only.
     *
     * @param Closure|CarbonInterface|string|null $testNow real or mock Carbon instance
     */
    protected function setTestNow(Closure|CarbonInterface|string|null $testNow = null): void
    {
        $factory = $this->getClock();

        $factory->setTestNow($testNow);

        self::callIfAvailable(
            [Clock::class, 'set'],
            /**  @psalm-suppress TooManyArguments */
            static fn (): array => [new Clock($factory)],
        );

        foreach ($this->synchronizers as $synchronizer) {
            $synchronizer($factory);
        }
    }

    /**
     * Call a duration method and jump to resulted date.
     */
    private function callDurationMethodAndJump(
        string $method,
        string|int|float|DateInterval $duration,
        ?float $speed = null,
    ): void {
        if (is_int($duration) || is_float($duration)) {
            $duration = "$duration seconds";
        }

        $this->jumpTo(Carbon::now()->$method($duration), $speed);
    }

    /** @param Closure|array $parameters */
    private function callIfAvailable(mixed $maybeCallable, Closure|array $parameters = []): mixed
    {
        if (is_callable($maybeCallable)) {
            return $maybeCallable(...(is_array($parameters) ? $parameters : $parameters()));
        }

        return null;
    }
}
