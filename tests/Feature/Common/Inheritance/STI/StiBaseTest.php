<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Feature\Common\Inheritance\STI;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\PromiseMapper\Tests\Support\PromiseMapperTestCase;
use Cycle\ORM\Schema;
use Testo\Lifecycle\BeforeTest;

abstract class StiBaseTest extends PromiseMapperTestCase
{
    #[BeforeTest]
    protected function setUp(): void
    {
        $factory = (new Factory(
            $this->dbal,
            RelationConfig::getDefault(),
            null,
            new ArrayCollectionFactory(),
        ));

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()))->with(factory: $factory);
    }

    abstract protected function getSchemaArray(): array;
}
