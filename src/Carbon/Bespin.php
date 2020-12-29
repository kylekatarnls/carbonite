<?php

namespace Carbon;

use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\JumpTo;
use Carbon\Carbonite\Attribute\Speed;
use Carbon\Carbonite\ReflectionCallable;
use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionNamedType;

class Bespin
{
    protected static function getFirstParameterType(callable $callable): string
    {
        $callable = Closure::fromCallable($callable);
        $parameters = (new ReflectionFunction($callable))->getParameters();

        if (!count($parameters)) {
            throw new InvalidArgumentException(
                'Passed callable should have at least 1 attribute as parameter.'
            );
        }

        $firstParameter = $parameters[0]->getType();

        if (!$firstParameter instanceof ReflectionNamedType) {
            throw new InvalidArgumentException(
                'First parameter of the callable should be typed as per the expected attribute to filter.'
            );
        }

        return $firstParameter->getName();
    }

    /** @return object[] */
    protected static function getTestMethods($test): iterable
    {
        $method = new ReflectionCallable($test);

        foreach ($method->getAttributes() as $attribute) {
            yield $attribute->newInstance();
        }

        $doc = preg_replace('`^/\s*\*+([\s\S]*)\*/\s*$`', '$1', $method->getDocComment() ?: '');
        $doc = trim(preg_replace('`^[\t ]*\*`m', '', $doc));
        preg_match_all('`^[\t ]*@([^(@\s]+)\(([^)]+)\)`m', $doc, $annotations, PREG_SET_ORDER);

        foreach ($annotations as [, $type, $parameters]) {
            yield @eval(
                'return new '.static::getTypeFullQualifiedName($method, $type).'('.$parameters.');'
            );
        }
    }

    protected static function getTypeFullQualifiedName(ReflectionCallable $method, string $type): string
    {
        [$baseNameSpace, $nameEnd] = array_pad(explode('\\', $type, 2), 2, '');
        $file = $method->getFileName();

        if ($file && $baseNameSpace !== '') {
            $contents = file_get_contents($file) ?: '';
            $useRegExp = '`^[\t ]*use\s+(\S[^\n]*)\\\\'.preg_quote($baseNameSpace, '`').'\s*;`m';

            if (preg_match($useRegExp, $contents, $use)) {
                return '\\'.$use[1].'\\'.$type;
            }

            $useRegExp = '`^[\t ]*use\s+(\S[^\n]*)\s+as\s+'.preg_quote($baseNameSpace, '`').'\s*;`m';

            if (preg_match($useRegExp, $contents, $use)) {
                return '\\'.$use[1].($nameEnd === '' ? '' : '\\'.$nameEnd);
            }

            preg_match_all('`^[\t ]*use\s+(\S[^\n]*)\{([^}]+)}`m', $contents, $uses, PREG_SET_ORDER);

            foreach ($uses as [, $base, $imports]) {
                foreach (array_map('trim', explode(',', $imports)) as $import) {
                    [$use, $alias] = array_pad(
                        array_map('trim', preg_split('`\sas\s`', $base.$import) ?: []),
                        2,
                        null
                    );
                    $chunks = explode('\\', $use);

                    if ($alias === $baseNameSpace || end($chunks) === $baseNameSpace) {
                        return '\\'.$use.($nameEnd === '' ? '' : '\\'.$nameEnd);
                    }
                }
            }
        }

        return $type;
    }

    protected static function walkElse($test, array $walkers, ?callable $else = null): void
    {
        $count = 0;

        foreach ($walkers as $walk) {
            $expectedType = static::getFirstParameterType($walk);

            foreach (static::getTestMethods($test) as $instance) {
                if (is_a($instance, $expectedType)) {
                    $walk($instance);
                    $count++;
                }
            }
        }

        if ($else && !$count) {
            $else();
        }
    }

    public static function up($test): void
    {
        static::walkElse(
            $test,
            [
                static function (Freeze $attribute): void {
                    Carbonite::freeze(...$attribute->getArguments());
                },
                static function (Speed $attribute): void {
                    Carbonite::speed(...$attribute->getArguments());
                },
                static function (JumpTo $attribute): void {
                    Carbonite::jumpTo(...$attribute->getArguments());
                },
            ],
            static function (): void {
                Carbonite::freeze();
            }
        );
    }

    public static function down($test): void
    {
        static::walkElse(
            $test,
            [],
            static function (): void {
                Carbonite::release();
            }
        );
    }

    public static function test(callable $test)
    {
        static::up($test);
        $result = $test();
        static::down($test);

        return $result;
    }
}
