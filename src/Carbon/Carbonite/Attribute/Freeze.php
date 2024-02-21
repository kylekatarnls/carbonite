<?php

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

use Attribute;
use Carbon\Carbonite;
use DateInterval;
use DatePeriod;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

#[Attribute]
readonly class Freeze implements UpInterface
{
    public function __construct(
        private string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $toMoment = 'now',
        private float $speed = 0.0,
    ) {
    }

    public function up(): void
    {
        Carbonite::freeze($this->toMoment, $this->speed);
    }
}
