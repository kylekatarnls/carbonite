<?php

namespace Tests\Carbon;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\Carbonite;
use Generator;
use PHPUnit\Framework\TestCase;
use Throwable;

class DocumentationTest extends TestCase
{
    /**
     * @dataProvider getReadmeExamples
     */
    public function testReadmeExamples(string $example): void
    {
        Carbonite::mock(null);
        Carbonite::release();

        $code = str_replace('echo ', 'echo "\n", ', $example);
        $code = preg_replace('/^<\?php/', '', $code);
        $imports = [
            Carbonite::class,
            Carbon::class,
            CarbonInterval::class,
        ];

        foreach ($imports as $import) {
            $import = "use $import;";

            if (strpos($code, $import) === false) {
                $code = "$import\n$code";
            }
        }

        $code = preg_replace('#^//.* [Nn]ow i(?:t\')?s (.*)$#m', 'Carbonite::mock("$1"); Carbon::hasTestNow() || Carbon::setTestNow(Carbon::parse("$1"));', $code);
        $needMock = false;

        $code = preg_replace_callback('#^(u)?sleep\((.+)\);#m', function ($matches) use (&$needMock) {
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

        if ($needMock) {
            Carbonite::mock('2000-01-01'); Carbon::hasTestNow() || Carbon::setTestNow(Carbon::parse('2000-01-01'));
        }

        $output = [];

        try {
            ob_start();
            eval($code);
            $output = array_filter(explode("\n", trim(ob_get_contents() ?: '')), function ($line) {
                return $line !== '';
            });
            ob_end_clean();
        } catch (Throwable $exception) {
            self::fail($exception->getMessage()."\n\nin code:\n$code\n\nstack:\n".$exception->getTraceAsString());
        }

        preg_match_all('#//\s*outputs?:(.+)$#m', $example, $matches);
        $lines = array_map('trim', $matches[1]);

        self::assertSame($lines, $output, "Unexpected output for code:\n$code");
    }

    public function getReadmeExamples(): Generator
    {
        preg_match_all(
            '/```php([\s\S]+)```/U',
            file_get_contents(__DIR__.'/../../README.md') ?: '',
            $matches,
            PREG_PATTERN_ORDER
        );

        foreach ($matches[1] as $example) {
            yield [trim($example)];
        }
    }
}
