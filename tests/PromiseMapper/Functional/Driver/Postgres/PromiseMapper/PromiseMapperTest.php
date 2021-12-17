<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Postgres\PromiseMapper;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\PromiseMapper\PromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class PromiseMapperTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
