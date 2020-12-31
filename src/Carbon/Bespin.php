<?php

namespace Carbon;

use Carbon\Carbonite\Attribute\AttributeBase;
use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\JumpTo;
use Carbon\Carbonite\Attribute\Speed;
use Carbon\Carbonite\Attribute\UpInterface;
use Carbon\Carbonite\ReflectionCallable;
use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionNamedType;

class Bespin
{
    /**
     * @param object|callable|string $test
     *
     * @return iterable<UpInterface>
     */
    protected static function getTestMethods($test): iterable
    {
        $method = new ReflectionCallable($test);

        foreach ($method->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), UpInterface::class, true)) {
                yield $attribute->newInstance();
            }
        }

        $doc = preg_replace('`^/\s*\*+([\s\S]*)\*/\s*$`', '$1', $method->getDocComment() ?: '');
        $doc = trim(preg_replace('`^[\t ]*\*`m', '', $doc));
        preg_match_all('`^[\t ]*@([^(@\s]+)\(([^)]+)\)`m', $doc, $annotations, PREG_SET_ORDER);

        foreach ($annotations as [, $type, $parameters]) {
            $className = static::getTypeFullQualifiedName($method, $type);
            $instance = null;

            if (class_exists($className) && is_a($className, UpInterface::class, true)) {
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

            /**
             * @var string $base
             * @var string $imports
             */
            foreach ($uses as [, $base, $imports]) {
                foreach (array_map('trim', explode(',', $imports)) as $import) {
                    /**
                     * @var string      $use
                     * @var string|null $alias
                     */
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

    /**
     * @param object|callable|string $test
     */
    public static function up($test): void
    {
        $count = 0;

        foreach (static::getTestMethods($test) as $instance) {
            if ($instance instanceof UpInterface) {
                $instance->up();
                $count++;
            }
        }

        if (!$count) {
            Carbonite::freeze();
        }
    }

    public static function down(): void
    {
        Carbonite::release();
    }

    /**
     * @param callable $test
     *
     * @return mixed
     */
    public static function test(callable $test)
    {
        static::up($test);
        $result = $test();
        static::down();

        return $result;
    }
}
