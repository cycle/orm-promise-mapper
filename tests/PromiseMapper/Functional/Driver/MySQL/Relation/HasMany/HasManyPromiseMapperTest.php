<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\MySQL\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\Relation\HasMany\HasManyPromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class HasManyPromiseMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
