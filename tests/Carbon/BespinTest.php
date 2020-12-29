<?php

namespace Tests\Carbon;

use Carbon\Bespin;
use Carbon\Carbon;
use Carbon\Carbonite;
use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\Freeze as Frozen;
use Carbon\Carbonite\Attribute\JumpTo;
use Carbon\Carbonite\Attribute\Speed;
use Carbon\Carbonite\ReflectionCallable;
use Carbon\Carbonite\{Attribute\Freeze as Froze};
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Carbon\Bespin::getFirstParameterType
 * @covers \Carbon\Bespin::getTestMethods
 * @covers \Carbon\Bespin::getTypeFullQualifiedName
 * @covers \Carbon\Bespin::walkElse
 * @covers \Carbon\Bespin::up
 * @covers \Carbon\Bespin::down
 * @covers \Carbon\Bespin::test
 * @covers \Carbon\Carbonite\Attribute\AttributeBase::__construct
 * @covers \Carbon\Carbonite\Attribute\AttributeBase::getArguments
 * @covers \Carbon\Carbonite\ReflectionCallable::__construct
 * @covers \Carbon\Carbonite\ReflectionCallable::getAttributes
 * @covers \Carbon\Carbonite\ReflectionCallable::getDocComment
 * @covers \Carbon\Carbonite\ReflectionCallable::getFileName
 * @covers \Carbon\Carbonite\ReflectionCallable::getSource
 */
class BespinTest extends TestCase
{
    protected function setUp(): void
    {
        Bespin::up($this);
    }

    protected function tearDown(): void
    {
        Bespin::down($this);
    }

    /**
     * @Freeze("2020-12-03 15:00")
     */
    public function testPhpDoc(): void
    {
        self::assertSame('2020-12-03 15:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    /**
     * @\Carbon\Carbonite\Attribute\Freeze("2020-12-03 15:42")
     */
    public function testFullQualifiedName(): void
    {
        self::assertSame('2020-12-03 15:42:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    #[Freeze("2020-12-05 12:00")]
    public function testAttribute(): void
    {
        if (version_compare(PHP_VERSION, '8.0.0-rc1', '<')) {
            self::markTestSkipped('PHP 8 is required to use attributes.');
        }

        self::assertSame('2020-12-05 12:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(1.0, Carbonite::speed());
    }

    public function testNoAttribute(): void
    {
        $before = new DateTimeImmutable('-3 seconds');
        $date = Carbon::now();
        $after = new DateTimeImmutable();
        self::assertTrue($date < $after);
        self::assertTrue($date > $before);
        self::assertSame(0.0, Carbonite::speed());
    }

    /** @Frozen("2020-12-03 02:00") */
    public function testAlias(): void
    {
        self::assertSame('2020-12-03 02:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    /** @Froze("2020-12-03 02:00") */
    public function testUseGroup(): void
    {
        self::assertSame('2020-12-03 02:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    /** @JumpTo("2021-01-01") */
    public function testJanuaryFirst(): void
    {
        self::assertSame('01-01', Carbon::now()->format('m-d'));
        self::assertSame(1.0, Carbonite::speed());
    }

    /** @Speed(10) */
    public function testSpeed(): void
    {
        self::assertSame(10.0, Carbonite::speed());
    }

    public function testTest()
    {
        $speeds = [];

        Bespin::test(function () use (&$speeds) {
            $speeds[] = Carbonite::speed();
        });

        $speeds[] = Carbonite::speed();

        self::assertSame([0.0, 1.0], $speeds);
    }

    public function testUncallableTest()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Passed string cannot be resolved by reflection.');

        new ReflectionCallable('not-callable');
    }
}
