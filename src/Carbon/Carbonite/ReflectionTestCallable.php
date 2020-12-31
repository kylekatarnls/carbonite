<?php

declare(strict_types=1);

namespace Carbon\Carbonite;

use Carbon\Carbonite\Attribute\UpInterface;

class ReflectionTestCallable extends ReflectionCallable
{
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

    /**
     * @return iterable<UpInterface|object>
     */
    public function getUpAttributes(): iterable
    {
        foreach ($this->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), UpInterface::class, true)) {
                yield $attribute->newInstance();
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
}
