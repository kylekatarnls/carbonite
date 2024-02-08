<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\UpInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<array> */
final class DataGroup implements IteratorAggregate
{
    /** @var list<UpInterface|string|DateTimeInterface|list<UpInterface|string|DateTimeInterface>> */
    private $timeConfigs;

    /** @var array[] */
    private $dataSets;

    /**
     * @param list<UpInterface|string|DateTimeInterface|list<UpInterface|string|DateTimeInterface>> $timeConfigs
     * @param array[]                                                                               $dataSets
     */
    public function __construct(
        iterable $timeConfigs,
        iterable $dataSets
    ) {
        $this->timeConfigs = $timeConfigs;
        $this->dataSets = $dataSets;
    }

    /**
     * @param UpInterface|string|DateTimeInterface|list<UpInterface|string|DateTimeInterface> $timeConfig
     * @param array[]                                                                         $dataSets
     */
    public static function for(
        $timeConfig,
        iterable $dataSets
    ): self {
        return new self([$timeConfig], $dataSets);
    }

    /**
     * @param list<UpInterface|string|DateTimeInterface|list<UpInterface|string|DateTimeInterface>> $timeConfigs
     * @param array[]                                                                               $dataSets
     */
    public static function matrix(
        iterable $timeConfigs,
        iterable $dataSets
    ): self {
        return new self($timeConfigs, $dataSets);
    }

    /**
     * @param non-empty-string|DateTimeZone $timeZone
     */
    public static function withVariousDates(
        iterable $dataSets,
        $timeZone = 'UTC',
        array $dates = [],
        array $times = []
    ): self {
        $tz = $timeZone instanceof DateTimeZone ? $timeZone : new DateTimeZone($timeZone);
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

        return self::matrix(self::matrixDatesAndTimes($dates, $times), $dataSets);
    }

    public static function matrixDatesAndTimes(array $dates, array $times): Generator
    {
        foreach ($dates as $date) {
            foreach ($times as $time) {
                yield "$date $time";
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

    /** @return list<list<UpInterface>> */
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

    private function asUp($timeConfig): UpInterface
    {
        if ($timeConfig instanceof UpInterface) {
            return $timeConfig;
        }

        return new Freeze($timeConfig);
    }

    private function dumpTimeConfig($timeConfig): string
    {
        if ($timeConfig instanceof UpInterface) {
            $chunks = explode('\\', get_class($timeConfig));
            $properties = array_values((array) $timeConfig);

            return end($chunks).'('.$properties[0].')';
        }

        return '';
    }
}
