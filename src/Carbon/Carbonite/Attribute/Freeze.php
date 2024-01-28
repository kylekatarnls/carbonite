<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Carbon\Carbonite;
use Carbon\CarbonPeriod;
use DateInterval;
use DatePeriod;
use DateTimeInterface;

#[Attribute]
class Freeze implements UpInterface
{
    /** @var string|CarbonInterface|CarbonPeriod|CarbonInterval|DateTimeInterface|DatePeriod|DateInterval */
    private $toMoment;
    /** @var ?float */
    private $speed;

    public function __construct($toMoment = 'now', float $speed = 0.0)
    {
        $this->toMoment = $toMoment;
        $this->speed = $speed;
    }

    public function up(): void
    {
        Carbonite::freeze($this->toMoment, $this->speed);
    }
}
