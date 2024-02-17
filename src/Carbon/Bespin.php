<?php

declare(strict_types=1);

namespace Carbon;

use Carbon\Carbonite\ReflectionTestCallable;

final class Bespin
{
    public static function up(object|callable|string $test): void
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
     * @template T
     *
     * @param callable(): T $test
     *
     * @return T
     */
    public static function test(callable $test): mixed
    {
        self::up($test);

        try {
            return $test();
        } finally {
            self::down();
        }
    }
}
