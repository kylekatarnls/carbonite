<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\UpInterface;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use IteratorAggregate;
use Psr\Clock\ClockInterface;
use Traversable;

// phpcs:disable Generic.Files.LineLength
/** @implements IteratorAggregate<array> */
final class DataGroup implements IteratorAggregate
{
    /** @var iterable<UpInterface|string|DateTimeInterface|ClockInterface|list<UpInterface|string|DateTimeInterface|ClockInterface>> */
    private iterable $timeConfigs;

    private iterable $dataSets;

    /**
     * Return dataset with each line with time mocked by the given time config (Freeze by default, but can also
     * be JumpTo, Speed or a custom implementation of UpInterface).
     *
     * @param iterable<UpInterface|string|DateTimeInterface|ClockInterface|list<UpInterface|string|DateTimeInterface|ClockInterface>> $timeConfigs
     */
    public function __construct(
        iterable $timeConfigs,
        iterable $dataSets
    ) {
        $this->timeConfigs = $timeConfigs;
        $this->dataSets = $dataSets;
    }

    /**
     * Return dataset with each line with time mocked by the given time config (Freeze by default, but can also
     * be JumpTo, Speed or a custom implementation of UpInterface).
     *
     * @param UpInterface|string|DateTimeInterface|ClockInterface|list<UpInterface|string|DateTimeInterface|ClockInterface> $timeConfig
     */
    public static function for(
        UpInterface|string|DateTimeInterface|ClockInterface|array $timeConfig,
        iterable $dataSets
    ): self {
        return new self([$timeConfig], $dataSets);
    }

    /**
     * Return dataset with each line frozen with a date-time randomly picked between given min and max.
     */
    public static function between(
        DatePeriod|DateInterval|DateTimeInterface|string|null $min,
        DatePeriod|DateInterval|DateTimeInterface|string|null $max,
        int|iterable $dataSets,
    ): self {
        return self::for(
            RandomClock::between($min, $max),
            is_int($dataSets) ? range(1, $dataSets) : $dataSets,
        );
    }

    /**
     * Return a new dataset that test each set of the given list with each one of time-mocking configuration
     * of the given list. So N time-mocking configurations with M data sets return an iterator of N×M
     * elements.
     *
     * @param iterable<UpInterface|string|DateTimeInterface|ClockInterface|list<UpInterface|string|DateTimeInterface|ClockInterface>> $timeConfigs
     * @param iterable<array>                                                                                                         $dataSets
     */
    public static function matrix(
        iterable $timeConfigs,
        iterable $dataSets
    ): self {
        return new self($timeConfigs, $dataSets);
    }

    /**
     * Return a new dataset that test each set of the given list with each one of pre-defined date-times that
     * represent cases that commonly trigger edge-cases (end of day, end of February).
     *
     * @param DateTimeZone|array<DateTimeZone|string|null>|string|null $timeZone
     */
    public static function withVariousDates(
        iterable $dataSets = [[]],
        DateTimeZone|array|string|null $timeZone = null,
        array $dates = [],
        array $times = [],
    ): self {
        $timeZones = is_array($timeZone) ? array_values($timeZone) : [$timeZone];
        $tz = self::createTimeZone($timeZones[0] ?? null);
        $realNow = new DateTimeImmutable('now', $tz);
        $dates = array_merge($dates, [
            '2024-01-01', // Start of year
            '2024-06-15', // Middle of month
            '2024-02-29', // End of February in leap year
            '2025-02-28', // End of February in non-leap year
            '2036-12-31', // In the future
            '2200-11-30', // Far in the future (timestamp greater than 2^32)
            $realNow->format('Y-m-d'), // Real date
        ]);
        $times = array_merge($times, [
            '00:00', // Start of day
            '04:30', // Start of day
            '12:34:56.789012', // Arbitrary
            '23:59:59.123456', // Almost end of day
            '23:59:59.999999', // End of day
            $realNow->format('H:i:s.u'), // Real time
        ]);

        return self::matrix(self::matrixDatesAndTimes($dates, $times, $timeZones), $dataSets);
    }

    /**
     * Return an iterator concatenating date + time + timezone for each combination for given list of
     * dates, times and timezones.
     *
     * @param string[]                     $dates
     * @param string[]                     $times
     * @param (DateTimeZone|string|null)[] $timeZones
     *
     * @return Generator<string>
     */
    public static function matrixDatesAndTimes(array $dates, array $times, array $timeZones = ['UTC']): Generator
    {
        foreach ($dates as $date) {
            foreach ($times as $time) {
                foreach ($timeZones as $timeZone) {
                    yield "$date $time".self::getTimeZoneSuffix($timeZone);
                }
            }
        }
    }

    /** @return Traversable<array> */
    public function getIterator(): Traversable
    {
        $timeConfigGroups = $this->getTimeConfigs();
        $hasMatrix = (count($timeConfigGroups) > 1);
        $index = 0;

        foreach ($timeConfigGroups as $timeConfigs) {
            foreach ($this->dataSets as $key => $dataSet) {
                $name = is_string($key) && $hasMatrix
                    ? implode(', ', array_filter(array_map(
                        [$this, 'dumpTimeConfig'],
                        $timeConfigs
                    ))).' '.$key
                    : $index++;

                yield $name => array_merge(is_array($dataSet) ? $dataSet : [$dataSet], $timeConfigs);
            }
        }
    }

    /**
     * Get list of time config sets. Each item of the main array is a time-mocking configuration to be tested
     * against each set of data. Each item itself is an array as a time-mocking configuration can be composed
     * of multiple instances of UpInterface.
     *
     * @return list<list<UpInterface>>
     */
    public function getTimeConfigs(): array
    {
        $array = [];

        foreach ($this->timeConfigs as $timeConfigs) {
            if (!is_array($timeConfigs)) {
                $timeConfigs = [$timeConfigs];
            }

            $array[] = array_map([$this, 'asUp'], $timeConfigs);
        }

        return $array;
    }

    /** @psalm-suppress RiskyTruthyFalsyComparison */
    private static function getTimeZoneSuffix($timeZone): string
    {
        if ($timeZone instanceof DateTimeZone) {
            $timeZone = $timeZone->getName();
        }

        return $timeZone ? " $timeZone" : '';
    }

    private static function createTimeZone($tz): ?DateTimeZone
    {
        if ($tz === '' || $tz === null) {
            return null;
        }

        return $tz instanceof DateTimeZone ? $tz : new DateTimeZone($tz);
    }

    private function asUp($timeConfig): UpInterface
    {
        if ($timeConfig instanceof UpInterface) {
            return $timeConfig;
        }

        return new Freeze($timeConfig);
    }

    private function dumpTimeConfig(UpInterface $timeConfig): string
    {
        $chunks = explode('\\', get_class($timeConfig));
        $properties = array_values((array) $timeConfig);

        return end($chunks).'('.$properties[0].')';
    }
}
