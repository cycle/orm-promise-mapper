<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\MySQL\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\Inheritance\STI\ManyToManyTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
