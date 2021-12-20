<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\MySQL\Inheritance\JTI\Relation;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\ParentClassRelationsTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ParentClassRelationsTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
