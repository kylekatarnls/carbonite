<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbonite\Attribute\Freeze;
use Carbon\Carbonite\Attribute\JumpTo;
use Carbon\Carbonite\Attribute\Speed;
use Closure;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class ReflectionCallable
{
    /** @var ReflectionMethod|null */
    protected $method = null;

    /** @var ReflectionFunction|null */
    protected $function = null;

    /**
     * @param object|callable|string $test
     *
     * @psalm-suppress ArgumentTypeCoercion, PossiblyInvalidArgument, PossiblyInvalidMethodCall
     *
     * @suppress PhanUndeclaredMethod
     */
    public function __construct($test)
    {
        try {
            $this->function = $test instanceof Closure || is_string($test) ? new ReflectionFunction($test) : null;
        } catch (ReflectionException $functionException) {
            // noop
        }

        if (!$this->function) {
            try {
                $this->method = new ReflectionMethod(...(
                    is_array($test)
                        ? $test
                        : [$test, method_exists($test, 'getName') ? $test->getName(false) : 'run']
                ));
            } catch (ReflectionException $methodException) {
                throw new InvalidArgumentException(
                    'Passed '.(is_object($test) ? get_class($test) : gettype($test)).
                    ' cannot be resolved by reflection.',
                    0,
                    $functionException ?? $methodException
                );
            }
        }
    }

    /**
     * @return ReflectionMethod|ReflectionFunction
     *
     * @psalm-suppress NullableReturnStatement, InvalidNullableReturnType
     *
     * @suppress PhanTypeMismatchReturnNullable
     */
    public function getSource(): object
    {
        return $this->method ?? $this->function;
    }

    public function getDocComment(): string
    {
        return $this->getSource()->getDocComment() ?: '';
    }

    public function getFileContent(): string
    {
        $file = $this->getSource()->getFileName() ?: null;
        $contents = $file ? @file_get_contents($file) : false;

        return $contents
            ?: implode("\n", array_map(function (string $className): string {
                return "use $className;";
            }, [Freeze::class, Speed::class, JumpTo::class]));
    }

    /**
     * @return iterable<ReflectionAttribute>
     */
    public function getAttributes(): iterable
    {
        $source = $this->getSource();

        if (method_exists($source, 'getAttributes')) {
            foreach ($source->getAttributes() as $attribute) {
                yield $attribute;
            }
        }
    }
}
