<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\Carbonite;
use DateInterval;
use DatePeriod;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

#[Attribute]
class Freeze implements UpInterface
{
    private string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $toMoment;
    private float $speed;

    public function __construct(
        string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $toMoment = 'now',
        float $speed = 0.0,
    ) {
        $this->toMoment = $toMoment;
        $this->speed = $speed;
    }

    public function up(): void
    {
        Carbonite::freeze($this->toMoment, $this->speed);
    }
}
