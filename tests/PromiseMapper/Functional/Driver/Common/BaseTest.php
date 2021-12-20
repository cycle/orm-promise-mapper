<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Database;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Handler;
use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\EntityManager;
use Cycle\ORM\PromiseMapper\Tests\Traits\Loggable;
use Cycle\ORM\Factory;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\ORM;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    use Loggable;

    public const DRIVER = null;

    public static array $config;
    protected ?DatabaseManager $dbal = null;
    protected ?ORM $orm = null;
    protected int $numWrites = 0;
    private static array $driverCache = [];

    public function setUp(): void
    {
        $this->setUpLogger($this->getDriver());

        if (self::$config['debug'] ?? false) {
            $this->enableProfiling();
        }

        $this->dbal = new DatabaseManager(new DatabaseConfig());
        $this->dbal->addDatabase(
            new Database(
                'default',
                '',
                $this->getDriver()
            )
        );
    }

    public function tearDown(): void
    {
        $this->dropDatabase($this->dbal->database('default'));

        $this->orm = null;
        $this->dbal = null;

        if (\function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    public function getDriver(): DriverInterface
    {
        if (isset(static::$driverCache[static::DRIVER])) {
            return static::$driverCache[static::DRIVER];
        }

        $config = self::$config[static::DRIVER];
        if (!isset($this->driver)) {
            $this->driver = $config->driver::create($config);
        }

        return static::$driverCache[static::DRIVER] = $this->driver;
    }

    protected function dropDatabase(Database $database = null): void
    {
        if ($database === null) {
            return;
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();

            foreach ($schema->getForeignKeys() as $foreign) {
                $schema->dropForeignKey($foreign->getColumns());
            }

            $schema->save(Handler::DROP_FOREIGN_KEYS);
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    public function withSchema(SchemaInterface $schema): ORM
    {
        $this->orm = new ORM(
            new Factory(
                $this->dbal,
                RelationConfig::getDefault(),
                null,
                new ArrayCollectionFactory()
            ),
            $schema
        );

        return $this->orm;
    }

    protected function getDatabase(): Database
    {
        return $this->dbal->database('default');
    }

    protected function save(object ...$entities): void
    {
        $tr = new EntityManager($this->orm);
        foreach ($entities as $entity) {
            $tr->persist($entity);
        }
        $tr->run();
    }

    /**
     * Start counting update/insert/delete queries.
     */
    public function captureWriteQueries(): void
    {
        $this->numWrites = self::$logger->countWriteQueries();
    }

    public function assertNumWrites(int $numWrites): void
    {
        $queries = self::$logger->countWriteQueries() - $this->numWrites;

        if (!empty(self::$config['strict'])) {
            $this->assertSame(
                $numWrites,
                $queries,
                "Number of write SQL queries do not match, expected {$numWrites} got {$queries}."
            );
        }
    }
}
