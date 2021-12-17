<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\MySQL\PromiseMapper;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\PromiseMapper\PromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class PromiseMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
