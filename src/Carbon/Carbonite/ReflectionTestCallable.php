<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbonite\Attribute\UpInterface;
use InvalidArgumentException;

final class ReflectionTestCallable extends ReflectionCallable
{
    /** @var object|array|string|null */
    private object|array|string|null $test = null;

    public static function fromTestCase($test): self
    {
        $sortId = self::getSortId($test) ?? $test;

        if ($sortId === null) {
            throw new InvalidArgumentException('Unable to resolve the sortId');
        }

        try {
            $instance = new self($sortId);
        } catch (InvalidArgumentException) {
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
        yield from $this->getUpAttributes();
        yield from $this->getDataProvided();
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     *
     * @return iterable<UpInterface>
     */
    public function getUpAttributes(): iterable
    {
        foreach ($this->getSource()->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), UpInterface::class, true)) {
                yield $attribute->newInstance();
            }
        }
    }

    /** @return iterable<UpInterface> */
    public function getDataProvided(): iterable
    {
        foreach ($this->getTestProvidedData() as $data) {
            if ($data instanceof UpInterface) {
                yield $data;
            }
        }
    }

    private function getTestProvidedData(): iterable
    {
        if (is_object($this->test) && method_exists($this->test, 'providedData')) {
            yield from $this->test->providedData();
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
