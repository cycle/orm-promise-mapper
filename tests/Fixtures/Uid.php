<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

/**
 * A value object typecast from a string column: each cast yields a new instance.
 */
final class Uid implements \Stringable
{
    public function __construct(
        public readonly string $value,
    ) {}

    public static function cast(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
