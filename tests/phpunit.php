<?php

use PHPUnit\Runner\Version;

require_once __DIR__.'/../vendor/autoload.php';

$id = Version::id();
$phpunit10 = version_compare($id, '10.0', '>=');
$config = $phpunit10 ? __DIR__.'/../phpunit.xml' : __DIR__.'/../phpunit-8.xml';
echo "Picking $config for PHPUnit $id\n";

$phpunit = __DIR__.'/../vendor/phpunit/phpunit/phpunit';
$file = $argv[0];
$covers = false;
$argv = array_filter(array_slice($argv, 1), static function (string $parameter) use (&$covers) {
    if ($parameter !== '--coverage') {
        return true;
    }

    $covers = true;

    return false;
});
$argv[] = '--configuration='.$config;

if ($covers) {
    $argv[] = '--coverage-clover=clover.xml';
    $argv[] = '--coverage-text';
}

if ($phpunit10) {
    $argv[] = '--display-incomplete';
    $argv[] = '--display-skipped';
    $argv[] = '--display-deprecations';
    $argv[] = '--display-errors';
    $argv[] = '--display-notices';
    $argv[] = '--display-warnings';
}

$_SERVER['argv'] = $argv;
$GLOBALS['_composer_autoload_path'] = __DIR__.'/void.php';

$code = explode('<?php', file_get_contents(__DIR__.'/../vendor/phpunit/phpunit/phpunit'), 2)[1];

if (version_compare($id, '9.0', '<')) {
    error_reporting(E_ALL & ~(E_DEPRECATED | E_USER_DEPRECATED));
}

eval($code);
