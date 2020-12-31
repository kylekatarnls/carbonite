<?php

namespace Tests\Carbon\Fixtures;

use Carbon\Bespin;

class BadBespin extends Bespin
{
    public static function callWalk(callable $callback): void
    {
        static::walkElse(function () {}, [$callback]);
    }
}
