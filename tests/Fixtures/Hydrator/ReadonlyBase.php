<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator;

class ReadonlyBase
{
    public readonly int $width;
    private readonly string $secret;

    public function __construct(
        public readonly string $preset = 'constructor',
    ) {}

    public function getSecret(): string
    {
        return $this->secret;
    }
}
