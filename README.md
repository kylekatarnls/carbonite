# Carbonite

Freeze, accelerate, slow down time and many more with [Carbon](https://carbon.nesbot.com/).

You can use it with any PSR-compatible clock system and framework
or with any time mocking system.

[![Latest Stable Version](https://poser.pugx.org/kylekatarnls/carbonite/v/stable.png)](https://packagist.org/packages/kylekatarnls/carbonite)
[![GitHub Actions](https://github.com/kylekatarnls/carbonite/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/kylekatarnls/carbonite/actions)
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

If your config matches the requirements:
- PHP >= 8.2
- Carbon >= 3.0.2

It will install the latest version of this package, if you need to support older
PHP versions (up to 7.2), Carbon 2, or to use annotations over attributes, then
install the version 1.x:

```shell
composer require --dev kylekatarnls/carbonite:^1
```

Then you can browse the corresponding
[Carbonite v1 documentation](https://github.com/kylekatarnls/carbonite/tree/1.x?tab=readme-ov-file#usage).

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

[Example of Laravel unit test](https://github.com/kylekatarnls/carbonite-laravel-example/blob/master/tests/Unit/UserTest.php)

[Example of Laravel feature test](https://github.com/kylekatarnls/carbonite-laravel-example/blob/master/tests/Feature/WelcomeTest.php)

[Example of raw PHPUnit test](#phpunit-example)

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

echo (int) Carbon::now()->diffInSeconds($now); // output: 0

// because first Carbon::now() is a few microseconds before the second one,
// so the final diff is a bit less than 1 second (for example 0.999262)

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
// it's like it's already 3am the next day
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

### do

`do($moment, callable $action)`

Trigger a given $action in a frozen instant $testNow. And restore previous moment and
speed once it's done, rather it succeeded or threw an error or an exception.

Returns the value returned by the given $action.

```php
Carbonite::freeze('2000-01-01', 1.5);
Carbonite::do('2020-12-23', static function () {
    echo Carbon::now()->format('Y-m-d H:i:s.u'); // output: 2020-12-23 00:00:00.000000
    usleep(200);
    // Still the same output as time is frozen inside the callback
    echo Carbon::now()->format('Y-m-d H:i:s.u'); // output: 2020-12-23 00:00:00.000000
    echo Carbonite::speed(); // output: 0
});
// Now the speed is 1.5 on 2000-01-01 again
echo Carbon::now()->format('Y-m-d'); // output: 2000-01-01
echo Carbonite::speed(); // output: 1.5
```

`Carbonite::do()` is a good way to isolate a test and use a particular date
as "now" then be sure to restore the previous state. If there is no previous
Carbonite state (if you didn't do any freeze, jump, speed, etc.) then `Carbon::now()`
will just no longer be mocked at all.

### doNow

`doNow(callable $action)`

Trigger a given $action in the frozen current instant. And restore previous
speed once it's done, rather it succeeded or threw an error or an exception.

Returns the value returned by the given $action.

```php
// Assuming now is 17 September 2020 8pm
Carbonite::doNow(static function () {
    echo Carbon::now()->format('Y-m-d H:i:s.u'); // output: 2020-09-17 20:00:00.000000
    usleep(200);
    // Still the same output as time is frozen inside the callback
    echo Carbon::now()->format('Y-m-d H:i:s.u'); // output: 2020-09-17 20:00:00.000000
    echo Carbonite::speed(); // output: 0
});
// Now the speed is 1 again
echo Carbonite::speed(); // output: 1
```

It's actually a shortcut for `Carbonite::do('now', callable $action)`.

`Carbonite::doNow()` is a good way to isolate a test, stop the time for this test
then be sure to restore the previous state. If there is no previous
Carbonite state (if you didn't do any freeze, jump, speed, etc.) then `Carbon::now()`
will just no longer be mocked at all.

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

### addSynchronizer

`addSynchronizer(callable $synchronizer): void`

Register a callback that will be executed every time mock value is changed.

The callback receives the default `\Carbon\FactoryImmutable` as parameter.

### removeSynchronizer

`removeSynchronizer(callable $synchronizer): void`

Remove a callback that has been registered with `addSynchronizer()`.

### mock

`mock($testNow): void`

Set the "real" now moment, it's a mock inception. It means that when you call `release()`
you will no longer go back to present but you will fallback to the mocked now. And the
mocked now will also determine the base speed to consider. If this mocked instance is
static, then "real" time will be frozen and so the fake timeline too no matter the speed
you chose.

This is a very low-level feature used for the internal unit tests of **Carbonite** and you
probably won't need this methods in your own code and tests, you more likely need the
[`freeze()`](#freeze) or [`jumpTo()`](#jumpto) method.

## Example with PSR-20 clock and frameworks such as Symfony

Symfony 7 `DatePoint` or service using any framework having
a clock system that can be mocked can be synchronized with
`Carbon\FactoryImmutable`. 

```php
use Carbon\Carbonite;
use Carbon\FactoryImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\DatePoint;

// \Symfony\Component\Clock\Clock is automatically synchronized
// So DatePoint and services linked to it will be mocked

Carbonite::freeze('2000-01-01');

$date = new DatePoint();
echo $date->format('Y-m-d'); // output: 2000-01-01

// Having a service using PSR Clock, you can also test it
// With any Carbonite method by passing Carbonite::getClock()
class MyService
{
    private $clock;

    public function __construct(ClockInterface $clock)
    {
        $this->clock = $clock;
    }
    
    public function getDate()
    {
        return $this->clock->now()->format('Y-m-d');
    }
}

$service = new MyService(Carbonite::getClock());
Carbonite::freeze('2025-12-20');
echo $service->getDate(); // output: 2025-12-20
```

If you have any other time-mocking system, you can synchronize
them with `freeze` and `jumpTo` attribute using
`addSynchronizer` in the bootstrap file of you test,
for instance if you use
[Timecop-PHP](https://github.com/runkit7/Timecop-PHP):

```php
use Carbon\Carbonite;
use Carbon\FactoryImmutable;

Carbonite::addSynchronizer(function (FactoryImmutable $factory) {
    Timecop::travel($factory->now()->timestamp);
});
```

## PHPUnit example

```php
use Carbon\BespinTimeMocking;
use Carbon\Carbonite;
use Carbon\CarbonPeriod;
use PHPUnit\Framework\TestCase;

class MyProjectTest extends TestCase
{
    // Will handle attributes on each method before running it
    // and release the time after each test
    use BespinTimeMocking;

    public function testHolidays()
    {
        $holidays = CarbonPeriod::create('2019-12-23', '2020-01-06', CarbonPeriod::EXCLUDE_END_DATE);
        Carbonite::jumpTo('2019-12-22');

        $this->assertFalse($holidays->isStarted());

        Carbonite::elapse('1 day');

        $this->assertTrue($holidays->isInProgress());

        Carbonite::jumpTo('2020-01-05 22:00');

        $this->assertFalse($holidays->isEnded());

        Carbonite::elapse('2 hours');

        $this->assertTrue($holidays->isEnded());

        Carbonite::rewind('1 microsecond');

        $this->assertFalse($holidays->isEnded());
    }
}
```

PHP 8 attributes can also be used for
convenience. Enable it using `BespinTimeMocking` trait or `Bespin::up()`
on a given test suite:

### PHP Attributes
```php
use Carbon\BespinTimeMocking;
use Carbon\Carbon;
use Carbon\Carbonite;
use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\JumpTo;
use Carbon\Carbonite\Attribute\Speed;
use PHPUnit\Framework\TestCase;

class PHP8Test extends TestCase
{
    // Will handle attributes on each method before running it
    // and release the time after each test
    use BespinTimeMocking;

    #[Freeze("2019-12-25")]
    public function testChristmas()
    {
        // Here we are the 2019-12-25, time is frozen.
        self::assertSame('12-25', Carbon::now()->format('m-d'));
        self::assertSame(0.0, Carbonite::speed());
    }

    #[JumpTo("2021-01-01")]
    public function testJanuaryFirst()
    {
        // Here we are the 2021-01-01, but time is NOT frozen.
        self::assertSame('01-01', Carbon::now()->format('m-d'));
        self::assertSame(1.0, Carbonite::speed());
    }

    #[Speed(10)]
    public function testSpeed()
    {
        // Here we start from the real date-time, but during
        // the test, time elapse 10 times faster.
        self::assertSame(10.0, Carbonite::speed());
    }

    #[Release]
    public function testRelease()
    {
        // If no attributes have been used, Bespin::up() will use:
        // Carbonite::freeze('now')
        // But you can still use #[Release] to get a test with
        // real time
    }
}
```

See [Carbonite v1 documentation](https://github.com/kylekatarnls/carbonite/tree/1.x?tab=readme-ov-file#php-7)
for annotations support.

## `fakeAsync()` for PHP

If you're familiar with `fakeAsync()` and `tick()` of Angular testing tools, then you can get the same syntax in
your PHP tests using:

<i code-id="fake-async"></i>
```php
use Carbon\Carbonite;

function fakeAsync(callable $fn): void {
    Carbonite::freeze();
    $fn();
    Carbonite::release();
}

function tick(int $milliseconds): void {
    Carbonite::elapse("$milliseconds milliseconds");
}
```

And use it as below:
<i depends-on="fake-async"></i>
```php
use Carbon\Carbon;

fakeAsync(function () {
    $now = Carbon::now();
    tick(2000);

    echo $now->diffForHumans(); // output: 2 seconds ago
});
```

### Data Provider

When applying `use BespinTimeMocking;` on a PHPUnit
TestCase and using `#[DataProvider]`, `@dataProvider`,
`#[TestWith]` or `@testWith` you can insert `Freeze`,
`JumpTo`, `Release` or `Speed`, they will be used to
configure time mocking before starting the test then
removed from the passed parameters:
<i lint-only="in-class"></i>
```php
#[TestWith([new Freeze('2024-05-25'), '2024-05-24'])]
#[TestWith([new Freeze('2023-01-01'), '2022-12-31'])]
public function testYesterday(string $date): void
{
    self::assertSame($date, Carbon::yesterday()->format('Y-m-d'));
}

#[DataProvider('getDataSet')]
public function testNow(string $date): void
{
    self::assertSame($date, Carbon::now()->format('Y-m-d'));
}

public static function getDataSet(): array
{
    return [
        ['2024-05-25', new Freeze('2024-05-25')],
        ['2023-12-14', new Freeze('2024-05-25')],
    ];
}
```

You can combine it with periods, for instance to test that
something works every day of the month:
<i lint-only="in-class"></i>
```php
#[DataProvider('getDataSet')]
public function testDataProvider(): void
{
    $now = CarbonImmutable::now();
    self::assertSame($now->day, $now->addMonth()->day);
}

public static function getDataSet(): iterable
{
    yield from Carbon::parse('2023-01-01')
        ->daysUntil('2023-01-31')
        ->map(static fn ($date) => [new Freeze($date)]);
}
```
The test above will be for each day of January and will fail
on 29th, 30th and 31st because it overflows from February to
March.

The `DataGroup` helper allows you to build data providers
with multiple sets using the same time mock:
<i lint-only="in-class"></i>
```php
#[DataProvider('getDataSet')]
public function testDataProvider(string $date, int $days): void
{
    self::assertSame(
        $date,
        Carbon::now()->addDays($days)->format('Y-m-d')
    );
}

public static function getDataSet(): iterable
{
    yield from DataGroup::for(new Freeze('2024-05-25'), [
        ['2024-05-27', 2],
        ['2024-06-01', 7],
        ['2024-06-08', 14],
    ]);

    yield from DataGroup::for(new Freeze('2023-12-30'), [
        ['2023-12-31', 1],
        ['2024-01-06', 7],
        ['2024-02-03', 35],
    ]);

    yield from DataGroup::matrix([
        new Freeze('2024-05-25'),
        new Freeze('2023-12-14'),
    ], [
        'a' => ['2024-05-25'],
        'bb' => ['2023-12-14'],
    ]);
}
```

And also to build a matrix to test each time config
with each set:

<i lint-only="in-class"></i>
```php
#[DataProvider('getDataSet')]
public function testDataProvider(string $text): void
{
    // This test will be run 4 times:
    // - With current time mocked to 2024-05-25 and $text = "abc"
    // - With current time mocked to 2024-05-25 and $text = "def"
    // - With current time mocked to 2023-12-14 and $text = "abc"
    // - With current time mocked to 2023-12-14 and $text = "def"
}

public static function getDataSet(): DataGroup
{
    return DataGroup::matrix([
        new Freeze('2024-05-25'),
        new Freeze('2023-12-14'),
    ], [
        ['abc'],
        ['def'],
    ]);
}
```

A default `DataGroup::withVariousDates()` is provided to mock time
a various moment that are known to trigger edge-cases such as end
of day, end of February, etc.

<i lint-only="in-class"></i>
```php
#[DataProvider('getDataSet')]
public function testDataProvider(): void
{
}

public static function getDataSet(): DataGroup
{
    return DataGroup::withVariousDates();
}
```

It can be crossed with a dataset (so to test each set with each date),
timezone to be used can be changed (with a single one or a list of multiple
ones so to test each of them) and extra dates and times can be added:

<i lint-only="in-class"></i>
```php
#[DataProvider('getDataSet')]
public function testDataProvider(string $text, int $number): void
{
}

public static function getDataSet(): DataGroup
{
    return DataGroup::withVariousDates(
        [
            ['abc', 4],
            ['def', 6],
        ],
        ['America/Chicago', 'Pacific/Auckland'],
        ['2024-12-25', '2024-12-26'],
        ['12:00', '02:30']
    );
}
```

You can also pick date to mock time randomly between 2 bounds:

<i lint-only="in-class"></i>
```php
#[DataProvider('getDataSet')]
public function testDataProvider(): void
{
    // Will run 5 times, each time with now randomly picked between
    // 2024-06-01 00:00 and 2024-09-20 00:00
    // For instance: 2024-07-16 22:45:12.251637
}

public static function getDataSet(): DataGroup
{
    return DataGroup::between('2024-06-01', '2024-09-20', 5);
}
```

Random date picking can also be used with a dataset:

<i lint-only="in-class"></i>
```php
#[DataProvider('getDataSet')]
public function testDataProvider(string $letter): void
{
    // Will run with $letter = 'a' and now picked randomly
    // Will run with $letter = 'b' and now picked randomly
    // Will run with $letter = 'c' and now picked randomly
}

public static function getDataSet(): DataGroup
{
    return DataGroup::between('2024-06-01', '2024-09-20', ['a', 'b', 'c']);
}
```

### Custom attributes

You can create your own time mocking attributes implementing
`UpInterface`:

<i lint-only="1"></i>
```php
use Carbon\Carbonite;
use Carbon\Carbonite\Attribute\UpInterface;

#[\Attribute]
final class AtUserCreation implements UpInterface
{
    public function __construct(private string $username) {}

    public function up() : void
    {
        // Let's assume as an example that the code below is how to get
        // user creation as a Carbon or DateTime from a username in your app.
        $creationDate = User::where('Name', $username)->first()->created_at;
        Carbonite::freeze($creationDate);
    }
}
```

Then you can use the following attribute like this in your test:

<i lint-only="in-class"></i>
```php
#[AtUserCreation('Robin')]
public function testUserAge(): void
{
    Carbon::sleep(3);
    $ageInSeconds = (int) User::where('Name', $username)->first()->created_at->diffInSeconds();

    self::assertSame(3, $ageInSeconds);
}
```
