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
class JumpTo implements UpInterface
{
    /** @var string|CarbonInterface|CarbonPeriod|CarbonInterval|DateTimeInterface|DatePeriod|DateInterval */
    private $moment;
    /** @var ?float */
    private $speed;

    public function __construct($moment, ?float $speed = null)
    {
        $this->moment = $moment;
        $this->speed = $speed;
    }

    public function up(): void
    {
        Carbonite::jumpTo($this->moment, $this->speed);
    }
}
