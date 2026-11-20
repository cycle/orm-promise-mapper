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
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;

/**
 * A plain ORM with array collections, and query counters measured from a capture point.
 */
abstract class PromiseMapperTestCase extends OrmTestCase
{
    private int $numWrites = 0;
    private int $numReads = 0;

    #[BeforeTest]
    protected function resetQueryCounters(): void
    {
        $this->numWrites = 0;
        $this->numReads = 0;
    }

    protected function withSchema(SchemaInterface|array $schema): ORMInterface
    {
        return $this->orm = new ORM(
            new Factory($this->dbal, RelationConfig::getDefault(), null, new ArrayCollectionFactory()),
            \is_array($schema) ? new Schema($schema) : $schema,
        );
    }

    /**
     * Start counting update/insert/delete queries.
     */
    protected function captureWriteQueries(): void
    {
        $this->numWrites = $this->context->queryLog()->countWrites();
    }

    /**
     * Start counting read queries.
     */
    protected function captureReadQueries(): void
    {
        $this->numReads = $this->context->queryLog()->countReads();
    }

    protected function assertNumWrites(int $numWrites): void
    {
        $queries = $this->context->queryLog()->countWrites() - $this->numWrites;
        Assert::same($queries, $numWrites, "Number of write SQL queries do not match, expected {$numWrites} got {$queries}.");
    }

    protected function assertNumReads(int $numReads): void
    {
        $queries = $this->context->queryLog()->countReads() - $this->numReads;
        Assert::same($queries, $numReads, "Number of read SQL queries do not match, expected {$numReads} got {$queries}.");
    }
}
