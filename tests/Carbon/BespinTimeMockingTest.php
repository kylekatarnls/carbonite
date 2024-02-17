<?php

declare(strict_types=1);

namespace Tests\Carbon;

use Carbon\BespinTimeMocking;
use PHPUnit\Framework\Assert;
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
        ob_end_clean();

        self::assertSame(1, $testSpeed);
        self::assertSame(1, $testFreezeAttribute);
    }

    private function getNumberOfAssertionsPerformed(string $method): int
    {
        Assert::resetCount();
        $test = new BasicTest($method);
        $test->runBare();
        $test->addToAssertionCount(Assert::getCount());
        Assert::resetCount();

        return $test->numberOfAssertionsPerformed();
    }
}
