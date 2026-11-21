<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator;

class Child extends Base
{
    public readonly string $name;
    public int $uninitialized;
    private ?string $shadowed = 'child';

    public function getChildShadowed(): ?string
    {
        return $this->shadowed;
    }
}
