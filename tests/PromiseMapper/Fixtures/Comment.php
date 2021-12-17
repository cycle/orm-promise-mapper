<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

class Comment
{
    public ?int $id = null;
    public ?string $message = null;
    public User $user;
}
