<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbonite\Attribute\UpInterface;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<array> */
final class DataGroup implements IteratorAggregate
{
    /** @var (UpInterface|list<UpInterface>)[] */
    private $timeConfigs;

    /** @var array[] */
    private $dataSets;

    /**
     * @param (UpInterface|list<UpInterface>)[] $timeConfigs
     * @param array[]                           $dataSets
     */
    public function __construct(
        iterable $timeConfigs,
        iterable $dataSets
    ) {
        $this->timeConfigs = $timeConfigs;
        $this->dataSets = $dataSets;
    }

    /**
     * @param UpInterface|list<UpInterface> $timeConfig
     * @param array[]                       $dataSets
     */
    public static function for(
        $timeConfig,
        iterable $dataSets
    ): self {
        return new self([$timeConfig], $dataSets);
    }

    /**
     * @param list<UpInterface|list<UpInterface>> $timeConfigs
     * @param array[]                             $dataSets
     */
    public static function matrix(
        iterable $timeConfigs,
        iterable $dataSets
    ): self {
        return new self($timeConfigs, $dataSets);
    }

    /** @return Traversable<array> */
    public function getIterator(): Traversable
    {
        $hasMatrix = (count($this->timeConfigs) > 1);
        $index = 0;

        foreach ($this->timeConfigs as $timeConfigs) {
            if (!is_array($timeConfigs)) {
                $timeConfigs = [$timeConfigs];
            }

            foreach ($this->dataSets as $key => $dataSet) {
                $name = is_string($key) && $hasMatrix
                    ? implode(', ', array_filter(array_map(
                        [$this, 'dumpTimeConfig'],
                        $timeConfigs
                    ))).' '.$key
                    : $index++;

                yield $name => array_merge($dataSet, $timeConfigs);
            }
        }
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
