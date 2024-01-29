<?php

require_once __DIR__.'/../vendor/autoload.php';

// Emulate Timecop class for the README example
if (!class_exists('Timecop')) {
    eval('class Timecop {public static function travel(){}}');
}
