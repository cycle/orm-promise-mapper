<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Unit\PromiseMapper;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\User;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\Testing\ORM\Memory;

final class PromiseMapperTest
{
    private PromiseMapper $mapper;

    #[\Testo\Test]
    public function testInit(): void
    {
        \Testo\Assert::instanceOf($this->mapper->init(['id' => 1, 'email' => 'test@email.com', 'balance' => 100], User::class), User::class);
    }

    #[\Testo\Test]
    public function testInitClassNotExist(): void
    {
        \Testo\Expect::exception(SchemaException::class);

        \Testo\Assert::instanceOf($this->mapper->init(['id' => 1, 'email' => 'test@email.com', 'balance' => 100], 'foo'), User::class);
    }

    #[\Testo\Test]
    public function testHydrate(): void
    {
        $user = $this->mapper->hydrate(new User(), ['id' => 1, 'email' => 'test@email.com', 'balance' => 100]);

        \Testo\Assert::same($user->id, 1);
        \Testo\Assert::same($user->email, 'test@email.com');
        \Testo\Assert::same($user->balance, 100.0);
    }

    #[\Testo\Test]
    public function testExtract(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@email.com';
        $user->balance = 100;
        $user->comments = [];

        $data = $this->mapper->extract($user);

        \Testo\Assert::same($data, ['id' => 1, 'email' => 'test@email.com', 'balance' => 100.0, 'comments' => []]);
    }

    #[\Testo\Test]
    public function testFetchFields(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@email.com';
        $user->balance = 100;
        $user->comments = [];

        $data = $this->mapper->extract($user);

        \Testo\Assert::same($data, ['id' => 1, 'email' => 'test@email.com', 'balance' => 100.0, 'comments' => []]);
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $orm = Memory::orm(
            new Schema([
                User::class => [
                    SchemaInterface::ENTITY => User::class,
                    SchemaInterface::MAPPER => PromiseMapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'user',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => ['id', 'email', 'balance'],
                    SchemaInterface::SCHEMA => [],
                    SchemaInterface::RELATIONS => [],
                ],
            ]),
        );

        $this->mapper = new PromiseMapper($orm, User::class);
    }
}
