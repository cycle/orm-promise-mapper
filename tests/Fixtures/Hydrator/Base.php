<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator;

class Base
{
    public static string $registry = 'static';
    public ?int $id = null;
    protected ?string $level = null;
    private ?string $secret = null;
    private ?string $shadowed = 'base';

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function getBaseShadowed(): ?string
    {
        return $this->shadowed;
    }
}
