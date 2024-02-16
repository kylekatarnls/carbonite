<?php

declare(strict_types=1);

namespace Tests\Carbon\Carbonite;

use Carbon\Carbonite\ReflectionCallable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(ReflectionCallable::class)]
class ReflectionCallableTest extends TestCase
{
    public function testClassWithGetName(): void
    {
        $obj = new class() {
            public function getName()
            {
                return 'foobar';
            }

            public function foobar()
            {
                return 42;
            }
        };
        $callable = new ReflectionCallable($obj);
        /** @var ReflectionMethod $source */
        $source = $callable->getSource();

        self::assertInstanceOf(ReflectionMethod::class, $source);
        self::assertSame(get_class($obj), $source->class);
        self::assertSame('foobar', $source->name);
    }

    public function testClassWithoutGetName(): void
    {
        $obj = new class() {
            public function run()
            {
                return 42;
            }
        };
        $callable = new ReflectionCallable($obj);
        /** @var ReflectionMethod $source */
        $source = $callable->getSource();

        self::assertInstanceOf(ReflectionMethod::class, $source);
        self::assertSame(get_class($obj), $source->class);
        self::assertSame('run', $source->name);
    }

    public function testWithExactMethod(): void
    {
        $obj = new class() {
            public function foobar()
            {
                return 42;
            }
        };
        $callable = new ReflectionCallable([$obj, 'foobar']);
        /** @var ReflectionMethod $source */
        $source = $callable->getSource();

        self::assertInstanceOf(ReflectionMethod::class, $source);
        self::assertSame(get_class($obj), $source->class);
        self::assertSame('foobar', $source->name);
    }

    public function testInvalidArguments(): void
    {
        self::expectExceptionObject(new InvalidArgumentException(
            'Passed empty array cannot be resolved by reflection.',
            1
        ));

        new ReflectionCallable([]);
    }

    public function testUncallableTest(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Passed string cannot be resolved by reflection.');

        new ReflectionCallable('not-callable');
    }
}
