<?php

declare(strict_types=1);

namespace Tests\Carbon\Carbonite;

use Carbon\CarbonImmutable;
use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\DataGroup;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataGroup::class)]
class DataGroupTest extends TestCase
{
    public function testBasicMatrix(): void
    {
        $group = DataGroup::matrix(['2024-02-10', '2024-02-15'], [3, 6]);

        self::assertEquals([
            [3, new Freeze('2024-02-10')],
            [6, new Freeze('2024-02-10')],
            [3, new Freeze('2024-02-15')],
            [6, new Freeze('2024-02-15')],
        ], iterator_to_array($group));

        $feb10 = new DateTimeImmutable('2024-02-10');
        $group = DataGroup::matrix([$feb10, new Freeze('2024-02-15')], [3, 6]);

        self::assertEquals([
            [3, new Freeze($feb10)],
            [6, new Freeze($feb10)],
            [3, new Freeze('2024-02-15')],
            [6, new Freeze('2024-02-15')],
        ], iterator_to_array($group));
    }

    public function testKeyedMatrix(): void
    {
        $group = DataGroup::matrix(['2024-02-10', '2024-02-15'], ['three' => 3, 'six' => 6]);

        self::assertEquals([
            'Freeze(2024-02-10) three' => [3, new Freeze('2024-02-10')],
            'Freeze(2024-02-10) six'   => [6, new Freeze('2024-02-10')],
            'Freeze(2024-02-15) three' => [3, new Freeze('2024-02-15')],
            'Freeze(2024-02-15) six'   => [6, new Freeze('2024-02-15')],
        ], iterator_to_array($group));
    }

    public function testFor(): void
    {
        $group = DataGroup::for('2024-02-10', [3, 6]);

        self::assertEquals([
            [3, new Freeze('2024-02-10')],
            [6, new Freeze('2024-02-10')],
        ], iterator_to_array($group));
    }

    public function testWithVariousDates(): void
    {
        $group = iterator_to_array(DataGroup::withVariousDates(['three' => 3, 'six' => 6]));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00')],
            $group['Freeze(2024-01-01 00:00) three'],
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999')],
            $group['Freeze(2024-06-15 23:59:59.999999) six'],
        );

        $group = iterator_to_array(DataGroup::withVariousDates(['three' => 3, 'six' => 6], 'UTC'));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00 UTC')],
            $group['Freeze(2024-01-01 00:00 UTC) three'],
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999 UTC')],
            $group['Freeze(2024-06-15 23:59:59.999999 UTC) six'],
        );

        $group = iterator_to_array(DataGroup::withVariousDates(['three' => 3, 'six' => 6], 'Pacific/Auckland'));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00 Pacific/Auckland')],
            $group['Freeze(2024-01-01 00:00 Pacific/Auckland) three'],
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999 Pacific/Auckland')],
            $group['Freeze(2024-06-15 23:59:59.999999 Pacific/Auckland) six'],
        );

        $group = iterator_to_array(DataGroup::withVariousDates(
            ['three' => 3, 'six' => 6],
            [new DateTimeZone('Pacific/Auckland'), ''],
        ));

        self::assertCount(168, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00')],
            $group['Freeze(2024-01-01 00:00) three'],
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999 Pacific/Auckland')],
            $group['Freeze(2024-06-15 23:59:59.999999 Pacific/Auckland) six'],
        );

        $group = iterator_to_array(DataGroup::withVariousDates(
            ['three' => 3, 'six' => 6],
            '',
        ));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00')],
            $group['Freeze(2024-01-01 00:00) three'],
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999')],
            $group['Freeze(2024-06-15 23:59:59.999999) six'],
        );
    }

    public function testBetween(): void
    {
        $items = iterator_to_array(DataGroup::between('2024-02-01', '2024-03-01', [1, 2]));

        self::assertCount(2, $items);
        self::assertCount(2, $items[0]);
        self::assertCount(2, $items[1]);
        self::assertSame(1, $items[0][0]);
        self::assertSame(2, $items[1][0]);
        self::assertInstanceOf(Freeze::class, $items[0][1]);
        self::assertInstanceOf(Freeze::class, $items[1][1]);

        $items[0][1]->up();
        $date = CarbonImmutable::now()->format('Y-m-d');

        self::assertGreaterThanOrEqual('2024-02-01', $date);
        self::assertLessThanOrEqual('2024-03-01', $date);

        $items[1][1]->up();
        $date = CarbonImmutable::now()->format('Y-m-d');

        self::assertGreaterThanOrEqual('2024-02-01', $date);
        self::assertLessThanOrEqual('2024-03-01', $date);
    }
}
