<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures\Inheritance;

class RbacItemAbstract
{
    public string $name;
    public ?string $description = null;
    public $parents;
    public $children;

    public function __construct(string $name, string $description = null)
    {
        $this->name = $name;
        $this->description = $description;

        $this->parents = [];
        $this->children = [];
    }
}
