<?php

declare(strict_types=1);

namespace Tests\Carbon;

use Carbon\BespinTimeMocking;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BespinTimeMocking::class)]
class BespinTimeMockingTest extends TestCase
{
    public function testMocking(): void
    {
        ob_start();
        $testSpeed = $this->getNumberOfAssertionsPerformed('testSpeed');
        $testFreezeAttribute = $this->getNumberOfAssertionsPerformed('testFreezeAttribute');
        $testFreezeAnnotation = $this->getNumberOfAssertionsPerformed('testFreezeAnnotation');
        ob_end_clean();

        self::assertSame(1, $testSpeed);
        self::assertSame(1, $testFreezeAttribute);
        self::assertSame(1, $testFreezeAnnotation);
    }

    private function getNumberOfAssertionsPerformed(string $method): int
    {
        $test = new BasicTest($method);
        $run = $test->run();

        if (method_exists($test, 'numberOfAssertionsPerformed')) {
            return $test->numberOfAssertionsPerformed();
        }

        if (method_exists($run, 'count')) {
            return $run->count();
        }

        return $test->getNumAssertions();
    }
}
