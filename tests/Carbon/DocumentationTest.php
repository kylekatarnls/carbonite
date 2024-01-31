<?php

namespace Tests\Carbon;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\Carbonite;
use Carbon\CarbonPeriod;
use Carbon\FactoryImmutable;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use ReflectionMethod;
use SimpleXMLElement;
use Symfony\Component\Clock\DatePoint;
use Throwable;

/**
 * @coversDefaultClass \Carbon\Carbonite
 */
class DocumentationTest extends TestCase
{
    /**
     * @dataProvider getReadmeExamples
     *
     * @covers ::freeze
     */
    public function testReadmeExamples(string $example, string $lintOnly): void
    {
        Carbonite::mock(null);
        Carbonite::release();

        $code = (string) str_replace('echo ', 'echo "\n", ', $example);
        $code = (string) preg_replace('/^<\?php/', '', $code);

        if ($lintOnly === 'in-class') {
            $code = 'class C'.mt_rand(0, 999999999)."{\n$code\n}";
        }

        $imports = [
            Carbonite::class,
            Carbon::class,
            CarbonInterval::class,
            CarbonPeriod::class,
        ];

        foreach ($imports as $import) {
            $import = "use $import;";

            if (strpos($code, $import) === false) {
                $code = "$import\n$code";
            }
        }

        $code = (string) preg_replace(
            '#^//.* [Nn]ow i(?:t\')?s (.*)$#m',
            'Carbonite::mock("$1"); Carbon::hasTestNow() || Carbon::setTestNow(Carbon::parse("$1"));',
            $code
        );
        $needMock = false;

        $code = (string) preg_replace_callback('#^(u)?sleep\((.+)\);#m', function ($matches) use (&$needMock) {
            [, $u, $pause] = $matches;
            $needMock = true;

            if (empty($u)) {
                $pause *= 1000000;
            }

            return implode("\n", [
                '$moment = Carbon::now();',
                '$speed = Carbonite::speed();',
                'Carbonite::mock("2000-01-01");',
                'Carbonite::release();',
                'Carbonite::freeze($moment, $speed);',
                'Carbonite::mock(Carbon::parse("2000-01-01")->addMicroseconds('.$pause.'));',
            ]);
        }, $code);

        $factory = new FactoryImmutable();

        if (strpos($code, 'DatePoint') !== false) {
            if (!class_exists(DatePoint::class)) {
                self::markTestSkipped('Requires Symfony >= 7');
            }

            if (!($factory instanceof ClockInterface)) {
                self::markTestSkipped('Requires Carbon >= 2.69.0');
            }
        }

        if ($needMock) {
            Carbonite::mock('2000-01-01');

            if (!Carbon::hasTestNow()) {
                Carbon::setTestNow(Carbon::parse('2000-01-01'));
            }
        }

        $output = [];

        try {
            $level = error_reporting(E_ALL & ~(E_USER_DEPRECATED | E_DEPRECATED));
            ob_start();
            eval($code);
            $output = array_filter(explode("\n", trim(ob_get_contents() ?: '')), function ($line) {
                return $line !== '';
            });
        } catch (Throwable $exception) {
            self::fail($exception->getMessage()."\n\nin code:\n$code\n\nstack:\n".$exception->getTraceAsString());
        } finally {
            ob_end_clean();
            error_reporting($level);
        }

        if ($lintOnly) {
            self::assertTrue(true);

            return;
        }

        preg_match_all('#//\s*outputs?:(.+)$#m', $example, $matches);
        $lines = array_map('trim', $matches[1]);

        self::assertSame($lines, $output, "Unexpected output for code:\n$code");

        preg_match_all('/^class (.*) extends TestCase$/m', $example, $matches, PREG_PATTERN_ORDER);

        foreach ($matches[1] as $className) {
            if ($className === 'PHP8Test' && version_compare(PHP_VERSION, '8.0.0-rc1', '<')) {
                continue;
            }

            /** @var TestCase $testCase */
            $testCase = @eval('return new class() extends '.$className.' {
                private $methodName = "";

                public function __construct(?string $name = null)
                {
                    parent::__construct($name ?? get_class_methods(self::class)[0]);
                }

                public function forTest(string $name): self
                {
                    $test = new self($name);
                    $test->methodName = $name;

                    return $test;
                }

                public function getName(bool $withDataSet = true): string
                {
                    return $this->methodName;
                }
            };');

            foreach (get_class_methods($testCase) as $method) {
                if ($method !== 'testChristmas') {
                    continue;
                }

                if (preg_match('/^test[A-Z]/', $method)) {
                    $methodTester = $testCase->forTest($method);
                    $steps = [
                        'setUp',
                        'mockTimeWithBespin',
                        $method,
                        'releaseBespinTimeMocking',
                        'tearDown',
                    ];

                    foreach ($steps as $step) {
                        if (method_exists($testCase, $step)) {
                            $methodReflection = new ReflectionMethod($testCase, $step);
                            $methodReflection->setAccessible(true);
                            $methodReflection->invoke($methodTester);
                        }
                    }
                }
            }
        }
    }

    public static function getReadmeExamples(): Generator
    {
        $readme = (string) file_get_contents(__DIR__.'/../../README.md');
        $codes = [];

        preg_match_all(
            '/```php([\s\S]+)```/U',
            $readme,
            $matches,
            PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE
        );

        foreach ($matches[1] as [$example, $offset]) {
            $previousLines = explode(
                "\n",
                str_replace("\r", '', substr($readme, 0, $offset))
            );

            $previousLine = trim(array_slice($previousLines, -2)[0] ?? '');
            $code = trim(str_replace("\r", '', $example));
            $lintOnly = '';

            if (preg_match('/^<i.+><\/i>$/', $previousLine)) {
                $infoTag = new SimpleXMLElement($previousLine);
                $lintOnly = (string) ($infoTag['lint-only'] ?? '');
                $codeId = (string) ($infoTag['code-id'] ?? '');

                if ($codeId !== '') {
                    $codes[$codeId] = $code;

                    continue;
                }

                $dependsOn = (string) ($infoTag['depends-on'] ?? '');

                if ($dependsOn !== '') {
                    if (!isset($codes[$dependsOn])) {
                        throw new InvalidArgumentException("Code '$dependsOn' not found");
                    }

                    $code = $codes[$dependsOn]."\n$code";
                }
            }

            yield [$code, $lintOnly];
        }
    }
}
