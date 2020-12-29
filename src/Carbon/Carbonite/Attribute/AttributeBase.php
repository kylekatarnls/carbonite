<?php

declare(strict_types=1);

namespace Carbon\Carbonite\Attribute;

abstract class AttributeBase
{
    /** @var mixed[] */
    protected $arguments;

    /**
     * @param mixed ...$arguments
     */
    public function __construct(...$arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return mixed[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
