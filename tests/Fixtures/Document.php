<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

class Document
{
    public function __construct(
        public readonly Uid $id,
        public string $title,
    ) {}
}
