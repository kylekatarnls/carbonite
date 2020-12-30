<?php

namespace Carbon;

use Carbon\Carbonite\Attribute\AttributeBase;
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

    /**
     * @param object|callable|string $test
     *
     * @return iterable<object>
     */
    protected static function getTestMethods($test): iterable
    {
        $method = new ReflectionCallable($test);

        foreach ($method->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), AttributeBase::class, true)) {
                yield $attribute->newInstance();
            }
        }

        $doc = preg_replace('`^/\s*\*+([\s\S]*)\*/\s*$`', '$1', $method->getDocComment() ?: '');
        $doc = trim(preg_replace('`^[\t ]*\*`m', '', $doc));
        preg_match_all('`^[\t ]*@([^(@\s]+)\(([^)]+)\)`m', $doc, $annotations, PREG_SET_ORDER);

        foreach ($annotations as [, $type, $parameters]) {
            $className = static::getTypeFullQualifiedName($method, $type);
            $instance = null;

            if (class_exists($className)) {
                $instance = @eval('return new '.$className.'('.$parameters.');');
            }

            if ($instance) {
                yield $instance;
            }
        }
    }

    protected static function getTypeFullQualifiedName(ReflectionCallable $method, string $type): string
    {
        [$baseNameSpace, $nameEnd] = array_pad(explode('\\', $type, 2), 2, '');

        if ($baseNameSpace !== '') {
            $contents = $method->getFileContent();
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
        $walkersByType = [];

        foreach ($walkers as $walk) {
            $walkersByType[static::getFirstParameterType($walk)] = $walk;
        }

        foreach (static::getTestMethods($test) as $instance) {
            foreach ($walkersByType as $expectedType => $walk) {
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
