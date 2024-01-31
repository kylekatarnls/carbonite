<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbonite\Attribute\UpInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ReflectionTestCallable extends ReflectionCallable
{
    /** @var object|array|string|null */
    private $test = null;

    public static function fromTestCase($test): self
    {
        try {
            $instance = new self(self::getSortId($test) ?? $test);
        } catch (InvalidArgumentException $exception) {
            $instance = new self($test);
        }

        $instance->test = $test;

        return $instance;
    }

    /**
     * @return iterable<UpInterface>
     */
    public function getUps(): iterable
    {
        foreach ($this->getUpAttributesAndAnnotations() as $instance) {
            if ($instance instanceof UpInterface) {
                yield $instance;
            }
        }
    }

    /**
     * @return iterable<UpInterface|object>
     */
    public function getUpAttributesAndAnnotations(): iterable
    {
        yield from $this->getUpAttributes();
        yield from $this->getUpAnnotations();
        yield from $this->getDataProvided();
    }

    /**
     * @return iterable<UpInterface|object>
     */
    public function getUpAnnotations(): iterable
    {
        $doc = preg_replace('`^/\s*\*+([\s\S]*)\*/\s*$`', '$1', $this->getDocComment() ?: '');
        $doc = trim(preg_replace('`^[\t ]*\*`m', '', $doc));
        preg_match_all('`^[\t ]*@([^(@\s]+)\(([^)]+)\)`m', $doc, $annotations, PREG_SET_ORDER);

        foreach ($annotations as [, $type, $parameters]) {
            $instance = $this->getUpAnnotationInstance($type, $parameters);

            if ($instance) {
                yield $instance;
            }
        }
    }

    /** @return iterable<UpInterface|object> */
    public function getUpAttributes(): iterable
    {
        foreach ($this->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), UpInterface::class, true)) {
                yield $attribute->newInstance();
            }
        }
    }

    /** @return iterable<UpInterface> */
    public function getDataProvided(): iterable
    {
        if (is_object($this->test) && method_exists($this->test, 'providedData')) {
            /** @var TestCase $test */
            $test = $this->test;
            $rest = [];
            $dataChanged = false;

            foreach ($this->test->providedData() as $data) {
                if ($data instanceof UpInterface) {
                    $dataChanged = true;
                    yield $data;

                    continue;
                }

                $rest[] = $data;
            }

            if ($dataChanged) {
                $property = new ReflectionProperty(TestCase::class, 'data');
                $property->setAccessible(true);
                $property->setValue($test, $rest);
            }
        }
    }

    /**
     * @return UpInterface|object|null
     */
    protected function getUpAnnotationInstance(string $type, string $parameters): ?object
    {
        $className = $this->getTypeFullQualifiedName($type);

        if (class_exists($className) && is_a($className, UpInterface::class, true)) {
            return @eval('return new '.$className.'('.$parameters.');');
        }

        return null;
    }

    protected function getTypeFullQualifiedName(string $type): string
    {
        [$baseNameSpace, $nameEnd] = array_pad(explode('\\', $type, 2), 2, '');

        if ($baseNameSpace !== '') {
            $contents = $this->getFileContent();
            $quotedNameSpace = preg_quote($baseNameSpace, '`');

            if (preg_match(
                "`^
                [\t ]*use\s+(?<import>\S[^\n]*)(
                    \\\\$quotedNameSpace |
                    \s+(?<as>as)\s+$quotedNameSpace
                )\s*;
                `mx",
                $contents,
                $use
            )) {
                return '\\'.$use['import'].(isset($use['as']) ? ($nameEnd === '' ? '' : '\\'.$nameEnd) : '\\'.$type);
            }

            foreach ($this->getImportFromGroups($contents, $baseNameSpace, $nameEnd) as $import) {
                return $import;
            }
        }

        return $type;
    }

    protected function getImportFromGroups(string $contents, string $baseNameSpace, string $nameEnd): iterable
    {
        /**
         * @var string      $use
         * @var string|null $alias
         */
        foreach ($this->parseGroupedImports($contents) as [$use, $alias]) {
            $chunks = explode('\\', $use);

            if ($alias === $baseNameSpace || end($chunks) === $baseNameSpace) {
                yield '\\'.$use.($nameEnd === '' ? '' : '\\'.$nameEnd);
            }
        }
    }

    protected function parseGroupedImports(string $contents): iterable
    {
        preg_match_all('`^[\t ]*use\s+(\S[^\n]*){([^}]+)}`m', $contents, $uses, PREG_SET_ORDER);

        /**
         * @var string $base
         * @var string $imports
         */
        foreach ($uses as [, $base, $imports]) {
            foreach (array_map('trim', explode(',', $imports)) as $import) {
                yield array_pad(
                    array_map('trim', preg_split('`\sas\s`', $base.$import) ?: []),
                    2,
                    null
                );
            }
        }
    }

    private static function getSortId($test): ?array
    {
        if (is_object($test) && method_exists($test, 'sortId')) {
            return explode(
                '::',
                explode(' ', $test->sortId())[0]
            );
        }

        return null;
    }
}
