<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\SQLite\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToPromiseMapperRenamedFieldsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class BelongsToPromiseMapperRenamedFieldsTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
