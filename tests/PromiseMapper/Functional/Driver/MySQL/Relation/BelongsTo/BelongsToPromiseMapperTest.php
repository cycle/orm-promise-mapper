<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\MySQL\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToPromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class BelongsToPromiseMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
