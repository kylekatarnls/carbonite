<?php

declare(strict_types=1);

namespace Tests\Carbon;

use Carbon\Bespin;
use Carbon\BespinTimeMocking;
use Carbon\Carbon;
use Carbon\Carbonite;
// @codingStandardsIgnoreStart
use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\Freeze as Frozen;
use Carbon\Carbonite\Attribute\JumpTo;
use Carbon\Carbonite\Attribute\Release;
use Carbon\Carbonite\Attribute\Speed;
use Carbon\Carbonite\Attribute\UpInterface;
use Carbon\Carbonite\ReflectionCallable;
use Carbon\Carbonite\ReflectionTestCallable;
use Carbon\Carbonite\{Attribute\Freeze as Froze};
// @codingStandardsIgnoreEnd
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bespin::class)]
#[CoversClass(Freeze::class)]
#[CoversClass(JumpTo::class)]
#[CoversClass(Speed::class)]
#[CoversClass(Release::class)]
#[CoversClass(ReflectionCallable::class)]
#[CoversClass(ReflectionTestCallable::class)]
class BespinTest extends TestCase
{
    use BespinTimeMocking;

    #[Freeze('2020-12-03 15:00')]
    public function testPhpDoc(): void
    {
        self::assertSame('2020-12-03 15:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    #[\Carbon\Carbonite\Attribute\Freeze('2020-12-03 15:42')]
    public function testFullQualifiedName(): void
    {
        self::assertSame('2020-12-03 15:42:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    #[Freeze('2020-12-05 12:00')]
    public function testAttribute(): void
    {
        if (version_compare(PHP_VERSION, '8.0.0-rc1', '<')) {
            self::markTestSkipped('PHP 8 is required to use attributes.');
        }

        self::assertSame('2020-12-05 12:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
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

    #[Frozen('2020-12-03 02:00')]
    public function testAlias(): void
    {
        self::assertSame('2020-12-03 02:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    #[Froze('2020-12-03 02:00')]
    public function testUseGroup(): void
    {
        self::assertSame('2020-12-03 02:00:00', Carbon::now()->format('Y-m-d H:i:s'));
        self::assertSame(0.0, Carbonite::speed());
    }

    #[JumpTo('2021-01-01')]
    public function testJanuaryFirst(): void
    {
        self::assertSame('01-01', Carbon::now()->format('m-d'));
    }

    #[Speed(10)]
    public function testSpeed(): void
    {
        self::assertSame(10.0, Carbonite::speed());
    }

    public function testTest(): void
    {
        $speeds = [];

        Bespin::test(function () use (&$speeds) {
            $speeds[] = Carbonite::speed();
        });

        $speeds[] = Carbonite::speed();

        self::assertSame([0.0, 1.0], $speeds);
    }

    public function testAttributesAvailability(): void
    {
        $release = new Release();
        self::assertInstanceOf(UpInterface::class, $release);
        $release->up();
        self::assertSame(1.0, Carbonite::speed());

        $freeze = new Freeze('2000-01-01');
        self::assertInstanceOf(UpInterface::class, $freeze);
        $freeze->up();
        self::assertSame(0.0, Carbonite::speed());
        self::assertSame('2000-01-01', Carbon::now()->format('Y-m-d'));

        $speed = new Speed(2);
        self::assertInstanceOf(UpInterface::class, $speed);
        $speed->up();
        self::assertSame(2.0, Carbonite::speed());
        self::assertSame('2000-01-01', Carbon::now()->format('Y-m-d'));

        $jumpTo = new JumpTo('2020-02-20');
        self::assertInstanceOf(UpInterface::class, $jumpTo);
        $jumpTo->up();
        self::assertSame(2.0, Carbonite::speed());
        self::assertSame('2020-02-20', Carbon::now()->format('Y-m-d'));
    }

    public function testMethodArrayDefinition(): void
    {
        $class = new class() {
            #[Freeze('Monday')]
            public function first(): string
            {
                return \Carbon\Carbon::now()->dayName;
            }

            #[Freeze('Tuesday')]
            public function second(): string
            {
                return \Carbon\Carbon::now()->dayName;
            }
        };

        self::assertSame('Monday', Bespin::test([$class, 'first']));
        self::assertSame('Tuesday', Bespin::test([$class, 'second']));

        $class = eval("return new class() {
            #[\Carbon\Carbonite\Attribute\Freeze('Monday')]
            public function first(): string
            {
                return \Carbon\Carbon::now()->dayName;
            }

            #[\Carbon\Carbonite\Attribute\Freeze('Tuesday')]
            #[\Carbon\Carbonite\Attribute\Other('should not break')]
            #[Other('should not break')]
            public function second(): string
            {
                return \Carbon\Carbon::now()->dayName;
            }
        };");

        self::assertSame('Monday', Bespin::test([$class, 'first']));
        self::assertSame('Tuesday', Bespin::test([$class, 'second']));
    }
}
