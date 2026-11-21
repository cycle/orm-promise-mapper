<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

class ReadonlyIdUser
{
    public function __construct(
        public string $email,
        public readonly ?int $id = null,
    ) {}
}
