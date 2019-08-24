# Carbonite

Freeze, accelerate, slow down time and many more with [Carbon](https://carbon.nesbot.com/).

[![Latest Stable Version](https://poser.pugx.org/kylekatarnls/carbonite/v/stable.png)](https://packagist.org/packages/kylekatarnls/carbonite)
[![Build Status](https://travis-ci.org/kylekatarnls/carbonite.svg?branch=master)](https://travis-ci.org/kylekatarnls/carbonite)
[![Code Climate](https://codeclimate.com/github/kylekatarnls/carbonite/badges/gpa.svg)](https://codeclimate.com/github/kylekatarnls/carbonite)
[![Test Coverage](https://codeclimate.com/github/kylekatarnls/carbonite/badges/coverage.svg)](https://codeclimate.com/github/kylekatarnls/carbonite/coverage)
[![Issue Count](https://codeclimate.com/github/kylekatarnls/carbonite/badges/issue_count.svg)](https://codeclimate.com/github/kylekatarnls/carbonite)
[![StyleCI](https://styleci.io/repos/203951579/shield?branch=master&style=flat)](https://styleci.io/repos/203951579)

Carbonite allows you to write unit tests as you would tell a story for times concerns.

[Professionally supported nesbot/carbon is now available](https://tidelift.com/subscription/pkg/packagist-nesbot-carbon?utm_source=packagist-nesbot-carbon&utm_medium=referral&utm_campaign=readme)

## Install

```shell
composer require --dev kylekatarnls/carbonite
```

We install Carbonite with `--dev` because it's designed for tests. You watched enough Sci-Fi movies to know time travel
paradoxes are too dangerous for production.

## Usage

```php
<?php

use Carbon\Carbon;
use Carbon\Carbonite;

function scanEpoch() {
    switch (Carbon::now()->year) {
        case 1944:
            return 'WW2';
        case 1946:
            return 'War is over';
        case 2255:
            return 'The Discovery is flying!';
    }
}

Carbonite::freeze('1944-05-05');

echo scanEpoch(); // output: WW2

Carbonite::elapse('2 years');

echo scanEpoch(); // output: War is over

Carbonite::jumpTo('2255-01-01 00:00:00');

echo scanEpoch(); // output: The Discovery is flying!

Carbonite::speed(3); // Times passes now thrice as fast
sleep(15); // If 15 seconds passes in the real time

// Then 45 seconds passed in our fake timeline:
echo Carbon::now(); // output: 2255-01-01 00:00:45
```

You can also use `CarbonImmutable`, both will be synchronized.

And as `Carbonite` directly handle any date created with `Carbon`, it will work just fine for properties like
created_at, updated_at or any custom date field in your Laravel models or any framework using `Carbon`.

## Available methods

### freeze

`Carbonite::freeze($toMoment = 'now', float $speed = 0.0): void`

Freeze the time to a given moment (now by default).

```php
// Assuming now is 2005-04-01 15:56:23

Carbonite::freeze(); // Freeze our fake timeline on current moment 2005-04-01 15:56:23

// You run a long function, so now is 2005-04-01 15:56:24
echo Carbon::now(); // output: 2005-04-01 15:56:23
// Time is frozen for any relative time in Carbon

Carbonite::freeze('2010-05-04');

echo Carbon::now()->isoFormat('MMMM [the] Do'); // output: May the 4th
```

This is particularly useful to avoid the small microseconds/seconds gaps that appear randomly in
unit tests when you do date-time comparison.

For example:
```php
$now = Carbon::now()->addSecond();

echo Carbon::now()->diffInSeconds($now); // output: 0

// because first Carbon::now() is a few microseconds before the second one,
// so the final diff is a bit less than 1 second

Carbonite::freeze();

$now = Carbon::now()->addSecond();

echo Carbon::now()->diffInSeconds($now); // output: 1

// Time is frozen so the whole thing behaves as if it was instantaneous
```

The first argument can be a string, a DateTime/DateTimeImmutable, Carbon/CarbonImmutable instance.
But it can also be a DateInterval/CarbonInterval (to add to now) or a DatePeriod/CarbonPeriod to
jump to the start of this period.

As a second optional parameter you can choose the new time speed after the freeze (0 by default).
```php
Carbonite::freeze('2010-05-04', 2); // Go to 2010-05-04 then make the time pass twice as fast.
```

See [speed() method](#speed).

### speed

`Carbonite::speed(float $speed = null): float`

Called without arguments `Carbonite::speed()` gives you the current speed of the fake timeline
(0 if frozen).

With an argument, it will set the speed to the given value:
```php
// Assuming now is 19 October 1977 8pm
Carbonite::freeze();
// Sit in the movie theater
Carbonite::speed(1 / 60); // Now every minute flies away like it's just a second
// 121 minutes later, now is 19 October 1977 10:01pm
// But it's like it's just 8:02:01pm in your timeline
echo Carbon::now()->isoFormat('h:mm:ssa'); // output: 8:02:01pm

// Now it's 19 October 1977 11:00:00pm
Carbonite::jumpTo('19 October 1977 11:00:00pm');
Carbonite::speed(3600); // and it's like every second was an hour
// 4 seconds later, now it's 19 October 1977 11:00:04pm
// it's like it's alreay 3am the next day
echo Carbon::now()->isoFormat('YYYY-MM-DD h:mm:ssa'); // output: 1977-10-20 3:00:00am
```

### fake

`Carbonite::fake(CarbonInterface $realNow): CarbonInterface`

Get fake now instance from real now instance.

```php
// Assuming now is 2020-03-14 12:00

Carbonite::freeze('2019-12-23'); // Set fake timeline to last December 23th (midnight)
Carbonite::speed(1); // speed = 1 means each second elapsed in real file, elpase 1 second in the fake timeline

// Then we can see what date and time it would be in the fake time line
// if we were let's say March the 16th in real life:

echo Carbonite::fake(Carbon::parse('2020-03-16 14:00')); // output: 2019-12-25 02:00:00
// Cool it would be Christmas (2am) in our fake timeline
```

### accelerate

`accelerate(float $factor): float`

Speeds up the time in the fake timeline by the given factor; and returns the new speed.
`accelerate(float $factor): float`

```php
Carbonite::speed(2);

echo Carbonite::accelerate(3); // output: 6
```

### decelerate

`decelerate(float $factor): float`

Slows down the time in the fake timeline by the given factor; and returns the new speed.
`decelerate(float $factor): float`

```php
Carbonite::speed(5);

echo Carbonite::decelerate(2); // output: 2.5
```

### unfreeze

`unfreeze(): void`

Unfreeze the fake timeline.

```php
// Now it's 8:00am
Carbonite::freeze();
echo Carbonite::speed(); // output: 0

// Now it's 8:02am
// but time is frozen
echo Carbon::now()->format('g:i'); // output: 8:00

Carbonite::unfreeze();
echo Carbonite::speed(); // output: 1

// Our timeline restart where it was paused
// so now it's 8:03am
echo Carbon::now()->format('g:i'); // output: 8:01
```

### jumpTo

`jumpTo($moment, float $speed = null): void`

Jump to a given moment in the fake timeline keeping the current speed.

```php
Carbonite::freeze('2000-06-30');
Carbonite::jumpTo('2000-09-01');
echo Carbon::now()->format('Y-m-d'); // output: 2000-09-01
Carbonite::jumpTo('1999-12-20');
echo Carbon::now()->format('Y-m-d'); // output: 1999-12-20
```

A second parameter can be passed to change the speed after the jump. By default, speed is not changed.

### elapse

`elapse($duration, float $speed = null): void`

Add the given duration to the fake timeline keeping the current speed.

```php
Carbonite::freeze('2000-01-01');
Carbonite::elapse('1 month');
echo Carbon::now()->format('Y-m-d'); // output: 2000-02-01
Carbonite::elapse(CarbonInterval::year());
echo Carbon::now()->format('Y-m-d'); // output: 2001-02-01
Carbonite::elapse(new DateInterval('P1M3D'));
echo Carbon::now()->format('Y-m-d'); // output: 2001-03-04
```

A second parameter can be passed to change the speed after the jump. By default, speed is not changed.

### rewind

`rewind($duration, float $speed = null): void`

Subtract the given duration to the fake timeline keeping the current speed.

```php
Carbonite::freeze('2000-01-01');
Carbonite::rewind('1 month');
echo Carbon::now()->format('Y-m-d'); // output: 1999-12-01
Carbonite::rewind(CarbonInterval::year());
echo Carbon::now()->format('Y-m-d'); // output: 1998-12-01
Carbonite::rewind(new DateInterval('P1M3D'));
echo Carbon::now()->format('Y-m-d'); // output: 1998-10-29
```

A second parameter can be passed to change the speed after the jump. By default, speed is not changed.

### release

`release(): void`

Go back to the present and normal speed.

```php
// Assuming now is 2019-05-24
Carbonite::freeze('2000-01-01');
echo Carbon::now()->format('Y-m-d'); // output: 2000-01-01
echo Carbonite::speed(); // output: 0

Carbonite::release();
echo Carbon::now()->format('Y-m-d'); // output: 2019-05-24
echo Carbonite::speed(); // output: 1
```

### mock

`mock($testNow): void`

Set the "real" now moment, it's a mock inception. It means that when you call `release()`
You will no longer go back to present but you will fallback to the mocked now. And the
mocked now will also determine the base speed to consider. If this mocked instance is
static, then "real" time will be frozen and so the fake timeline too no matter the speed
you chose.

This is a very low-level feature used for the internal unit tests of **Carbonite** and you
probably won't need this methods in your own code and tests, you more likely need the
[`freeze()`](#freeze) or [`jumpTo()`](#jumpto) method.
