<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class ReflectionCallable
{
    protected ?ReflectionMethod $method = null;

    protected ?ReflectionFunction $function = null;

    /**
     * @param object|array|string $test
     *
     * @psalm-suppress ArgumentTypeCoercion, PossiblyInvalidArgument, PossiblyInvalidMethodCall
     *
     * @suppress PhanUndeclaredMethod
     */
    public function __construct(object|array|string $test)
    {
        try {
            $this->function = $test instanceof Closure || is_string($test) ? new ReflectionFunction($test) : null;
        } catch (ReflectionException $functionException) {
            // noop
        }

        if (!$this->function) {
            try {
                $this->method = new ReflectionMethod(...$this->getReflectionMethodParameters($test));
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
     * @psalm-suppress NullableReturnStatement, InvalidNullableReturnType
     *
     * @suppress PhanTypeMismatchReturnNullable
     */
    public function getSource(): ReflectionMethod|ReflectionFunction
    {
        return $this->method ?? $this->function;
    }

    /**
     * @param object|array|string $test
     *
     * @suppress PhanUndeclaredMethod
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress ArgumentTypeCoercion
     * @psalm-suppress PossiblyInvalidMethodCall
     *
     * @return array{object|class-string, string|null}
     */
    private function getReflectionMethodParameters(object|array|string $test): array
    {
        if (is_array($test)) {
            if ($test === []) {
                throw new InvalidArgumentException(
                    'Passed empty array cannot be resolved by reflection.',
                    1
                );
            }

            return $test;
        }

        return [$test, method_exists($test, 'getName') ? $test->getName(false) : 'run'];
    }
}
