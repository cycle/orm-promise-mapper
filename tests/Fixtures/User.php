<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

class User
{
    public ?int $id = null;
    public ?string $email = null;
    public ?float $balance = null;
    public $comments = [];
}
