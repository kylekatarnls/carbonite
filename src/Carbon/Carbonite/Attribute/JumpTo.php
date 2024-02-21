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
readonly class JumpTo implements UpInterface
{
    public function __construct(
        private string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $moment,
        private ?float $speed = null,
    ) {
    }

    public function up(): void
    {
        Carbonite::jumpTo($this->moment, $this->speed);
    }
}
