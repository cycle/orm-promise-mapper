<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Fixtures;

class Profile
{
    public int $id;
    public string $image;
    /** @var User|null */
    public $user;
}
