<?php

declare(strict_types=1);

namespace Carbon;

use Carbon\Carbonite\ReflectionTestCallable;

class Bespin
{
    /**
     * @param object|callable|string $test
     */
    public static function up($test): void
    {
        $method = ReflectionTestCallable::fromTestCase($test);
        $count = 0;

        foreach ($method->getUps() as $instance) {
            $instance->up();
            $count++;
        }

        if (!$count) {
            Carbonite::freeze();
        }
    }

    public static function down(): void
    {
        Carbonite::release();
    }

    /**
     * @param callable $test
     *
     * @return mixed
     */
    public static function test(callable $test)
    {
        static::up($test);
        $result = $test();
        static::down();

        return $result;
    }
}
