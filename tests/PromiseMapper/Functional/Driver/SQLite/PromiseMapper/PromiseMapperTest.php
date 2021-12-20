<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\SQLite\PromiseMapper;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\PromiseMapper\PromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class PromiseMapperTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
