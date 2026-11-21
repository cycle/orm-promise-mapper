<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

class ReadonlyUser
{
    public ?int $id = null;

    public function __construct(
        public readonly string $email,
    ) {}
}
