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

Carbonite::speed(3); // Times passes now thrice faster
sleep(15); // If 15 seconds passes in the real time

// Then 45 seconds passed in our fake timeline:
echo Carbon::now(); // output: 2255-01-01 00:00:45
```

You can also use `CarbonImmutable`, both will be synchronized.

And as `Carbonite` directly handle any date created with `Carbon`, it will work just fine for properties like
created_at, updated_at or any custom date field in your Laravel models or any framework using `Carbon`.
