<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Support;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\Testing\ORM\OrmTestCase;

/**
 * A plain ORM with array collections.
 */
abstract class PromiseMapperTestCase extends OrmTestCase
{
    protected function withSchema(SchemaInterface|array $schema): ORMInterface
    {
        return $this->orm = new ORM(
            new Factory($this->dbal, RelationConfig::getDefault(), null, new ArrayCollectionFactory()),
            \is_array($schema) ? new Schema($schema) : $schema,
        );
    }
}
