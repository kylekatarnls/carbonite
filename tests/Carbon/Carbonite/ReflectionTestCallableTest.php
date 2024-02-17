<?php

declare(strict_types=1);

namespace Tests\Carbon\Carbonite;

use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\Speed;
use Carbon\Carbonite\ReflectionTestCallable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReflectionTestCallable::class)]
class ReflectionTestCallableTest extends TestCase
{
    public function testEmptytDataProvided(): void
    {
        $obj = new class() {
            public function run()
            {
                return 42;
            }
        };
        $callable = ReflectionTestCallable::fromTestCase($obj);

        self::assertSame([], iterator_to_array($callable->getDataProvided()));
    }

    public function testGetDataProvided(): void
    {
        $freeze = new Freeze('2020');
        $speed = new Speed(0.5);
        $obj = new class($freeze, $speed) {
            private $freeze;
            private $speed;

            public function __construct($freeze, $speed)
            {
                $this->freeze = $freeze;
                $this->speed = $speed;
            }

            public function run()
            {
                return 42;
            }

            public function providedData()
            {
                yield 42;
                yield $this->freeze;
                yield $this;
                yield $this->speed;
            }
        };
        $callable = ReflectionTestCallable::fromTestCase($obj);

        self::assertSame([$freeze, $speed], iterator_to_array($callable->getDataProvided()));
    }

    public function testNullArguments(): void
    {
        self::expectExceptionObject(new InvalidArgumentException(
            'Unable to resolve the sortId',
        ));

        ReflectionTestCallable::fromTestCase(null);
    }

    public function testInvalidArguments(): void
    {
        self::expectExceptionObject(new InvalidArgumentException(
            'Passed string cannot be resolved by reflection.',
            0,
        ));

        ReflectionTestCallable::fromTestCase('does-not-exist');
    }

    public function testSortId(): void
    {
        $obj = new class() {
            public function run()
            {
                return 42;
            }

            public function sortId()
            {
                return ReflectionTestCallableTest::class.'::sayHello extra data';
            }
        };
        $callable = ReflectionTestCallable::fromTestCase($obj);

        self::assertSame('hello', $callable->getSource()->invoke($this));
    }

    public function sayHello(): string
    {
        return 'hello';
    }
}
