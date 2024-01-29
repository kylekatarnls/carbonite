<?php

declare(strict_types=1);

namespace Carbon;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

trait BespinTimeMocking
{
    /** @before */
    #[Before]
    protected function mockTimeWithBespin(): void
    {
        Bespin::up($this);
    }

    /** @after */
    #[After]
    protected function releaseBespinTimeMocking(): void
    {
        Bespin::down();
    }
}
