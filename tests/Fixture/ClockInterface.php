<?php

/*
 * This file simulates \Symfony\Component\Clock\ClockInterface
 */

namespace Symfony\Component\Clock;

use Psr\Clock\ClockInterface as PsrClockInterface;

interface ClockInterface extends PsrClockInterface
{
    public function sleep($seconds);

    public function withTimeZone($timezone);
}
