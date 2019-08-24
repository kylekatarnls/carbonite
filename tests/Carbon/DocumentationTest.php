<?php

namespace Tests\Carbon;

use Carbon\Carbonite;
use Generator;
use PHPUnit\Framework\TestCase;

class DocumentationTest extends TestCase
{
    protected function setUp(): void
    {
        Carbonite::mock(null);
        Carbonite::release();
    }

    /**
     * @dataProvider getReadmeExamples
     */
    public function testReadmeExamples(string $example): void
    {
        $code = '?>'.str_replace('echo ', 'echo "\n", ', $example);
        ob_start();
        eval($code);
        $output = explode("\n", trim(ob_get_contents()));
        ob_end_clean();

        preg_match_all('#//\s*outputs?:(.+)$#m', $example, $matches);
        $lines = array_map('trim', $matches[1]);

        self::assertSame($lines, $output);
    }

    public function getReadmeExamples(): Generator
    {
        preg_match_all('/```php([\s\S]+)```/', file_get_contents(__DIR__.'/../../README.md'), $matches, PREG_PATTERN_ORDER);

        foreach ($matches[1] as $example) {
            yield [trim($example)];
        }
    }
}
