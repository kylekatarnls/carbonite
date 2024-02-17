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
class JumpTo implements UpInterface
{
    private string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $moment;
    private ?float $speed;

    public function __construct(
        string|DateTimeInterface|DatePeriod|DateInterval|ClockInterface $moment,
        ?float $speed = null,
    ) {
        $this->moment = $moment;
        $this->speed = $speed;
    }

    public function up(): void
    {
        Carbonite::jumpTo($this->moment, $this->speed);
    }
}
