<?php

declare(strict_types=1);

namespace Tests\Carbon;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Carbon\BespinTimeMocking
 */
class BespinTimeMockingTest extends TestCase
{
    public function testMocking(): void
    {
        ob_start();
        $test = new BasicTest('testSpeed');
        $test->run();
        $test = new BasicTest('testFreezeAttribute');
        $test->run();
        $test = new BasicTest('testFreezeAnnotation');
        $test->run();
        ob_end_clean();
    }
}
