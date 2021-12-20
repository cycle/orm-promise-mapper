<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures\Inheritance;

class Manager extends Employee
{
    public ?int $role_id = null;

    public ?int $level = null;
    public string $rank = 'none';
}
