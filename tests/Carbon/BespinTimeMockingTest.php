<?php

namespace Tests\Carbon;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Carbon\BespinTimeMocking
 */
class BespinTimeMockingTest extends TestCase
{
    public function testMocking(): void
    {
        $testGenerator = eval('return static function (string $name) {
            return new class ($name) extends \PHPUnit\Framework\TestCase {
                use \Carbon\BespinTimeMocking;

                /** @coversNothing */
                public function testSpeed(): void
                {
                    self::assertSame(0.0, \Carbon\Carbonite::speed());
                }

                /** @coversNothing */
                #[\Carbon\Carbonite\Attribute\Freeze("2024-01-15 08:00")]
                public function testFreeze(): void
                {
                    self::assertSame("2024-01-15 08:00", \Carbon\Carbon::now()->format("Y-m-d H:i"));
                }
            };
        };');

        ob_start();
        $testGenerator('testSpeed')->run();
        $testGenerator('testFreeze')->run();
        ob_end_clean();
    }
}
