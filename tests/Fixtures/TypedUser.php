<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

/**
 * Entity whose typed properties stay uninitialized until the ORM fills them.
 */
class TypedUser
{
    public int $id;
    public string $email;
}
