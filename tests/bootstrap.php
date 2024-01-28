<?php

use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\NativeClock;

require_once __DIR__.'/../vendor/autoload.php';

// Emulate Timecop class for the README example
if (!class_exists('Timecop')) {
    eval('class Timecop {public static function travel(){}}');
}

if (!class_exists(DatePoint::class)) {
    if (!interface_exists(ClockInterface::class)) {
        require __DIR__.'/Fixture/ClockInterface.php';
    }

    if (!class_exists(Clock::class)) {
        require __DIR__.'/Fixture/Clock.php';
    }

    if (!class_exists(NativeClock::class)) {
        require __DIR__.'/Fixture/NativeClock.php';
    }

    require __DIR__.'/Fixture/DatePoint.php';
}
