<?php

declare(strict_types=1);

namespace Tests\Carbon\Carbonite;

use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\DataGroup;
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
}
