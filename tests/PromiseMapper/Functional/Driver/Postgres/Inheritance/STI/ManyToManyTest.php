<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Postgres\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\Inheritance\STI\ManyToManyTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
