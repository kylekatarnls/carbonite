<?php

declare(strict_types=1);

namespace Tests\Carbon\Carbonite;

use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\DataGroup;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Carbon\Carbonite\DataGroup
 */
class DataGroupTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getTimeConfigs
     * @covers ::matrix
     * @covers ::getIterator
     * @covers ::asUp
     */
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

    /**
     * @covers ::__construct
     * @covers ::getTimeConfigs
     * @covers ::matrix
     * @covers ::getIterator
     * @covers ::asUp
     * @covers ::dumpTimeConfig
     */
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

    /**
     * @covers ::for
     * @covers ::getTimeConfigs
     */
    public function testFor(): void
    {
        $group = DataGroup::for('2024-02-10', [3, 6]);

        self::assertEquals([
            [3, new Freeze('2024-02-10')],
            [6, new Freeze('2024-02-10')],
        ], iterator_to_array($group));
    }

    /**
     * @covers ::withVariousDates
     * @covers ::matrixDatesAndTimes
     */
    public function testWithVariousDates(): void
    {
        $group = iterator_to_array(DataGroup::withVariousDates(['three' => 3, 'six' => 6]));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00')],
            $group['Freeze(2024-01-01 00:00) three']
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999')],
            $group['Freeze(2024-06-15 23:59:59.999999) six']
        );

        $group = iterator_to_array(DataGroup::withVariousDates(['three' => 3, 'six' => 6], 'UTC'));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00 UTC')],
            $group['Freeze(2024-01-01 00:00 UTC) three']
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999 UTC')],
            $group['Freeze(2024-06-15 23:59:59.999999 UTC) six']
        );

        $group = iterator_to_array(DataGroup::withVariousDates(['three' => 3, 'six' => 6], 'Pacific/Auckland'));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00 Pacific/Auckland')],
            $group['Freeze(2024-01-01 00:00 Pacific/Auckland) three']
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999 Pacific/Auckland')],
            $group['Freeze(2024-06-15 23:59:59.999999 Pacific/Auckland) six']
        );

        $group = iterator_to_array(DataGroup::withVariousDates(
            ['three' => 3, 'six' => 6],
            [new DateTimeZone('Pacific/Auckland'), '']
        ));

        self::assertCount(168, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00')],
            $group['Freeze(2024-01-01 00:00) three']
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999 Pacific/Auckland')],
            $group['Freeze(2024-06-15 23:59:59.999999 Pacific/Auckland) six']
        );

        $group = iterator_to_array(DataGroup::withVariousDates(
            ['three' => 3, 'six' => 6],
            ''
        ));

        self::assertCount(84, $group);
        self::assertEquals(
            [3, new Freeze('2024-01-01 00:00')],
            $group['Freeze(2024-01-01 00:00) three']
        );
        self::assertEquals(
            [6, new Freeze('2024-06-15 23:59:59.999999')],
            $group['Freeze(2024-06-15 23:59:59.999999) six']
        );
    }
}
