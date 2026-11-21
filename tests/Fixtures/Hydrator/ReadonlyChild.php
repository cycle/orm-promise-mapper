<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator;

final class ReadonlyChild extends ReadonlyBase
{
    public readonly string $src;
}
