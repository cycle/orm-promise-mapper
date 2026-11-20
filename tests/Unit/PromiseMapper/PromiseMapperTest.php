<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Unit\PromiseMapper;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Inheritance\Engineer;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Inheritance\Human;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\User;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\Testing\ORM\Memory;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class PromiseMapperTest
{
    private PromiseMapper $mapper;

    public function testInit(): void
    {
        Assert::instanceOf($this->mapper->init(['id' => 1, 'email' => 'test@email.com', 'balance' => 100], User::class), User::class);
    }

    #[ExpectException(SchemaException::class)]
    public function testInitClassNotExist(): void
    {
        Assert::instanceOf($this->mapper->init(['id' => 1, 'email' => 'test@email.com', 'balance' => 100], 'foo'), User::class);
    }

    public function testHydrate(): void
    {
        $user = $this->mapper->hydrate(new User(), ['id' => 1, 'email' => 'test@email.com', 'balance' => 100]);

        Assert::same($user->id, 1);
        Assert::same($user->email, 'test@email.com');
        Assert::same($user->balance, 100.0);
    }

    public function testExtract(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@email.com';
        $user->balance = 100;
        $user->comments = [];

        $data = $this->mapper->extract($user);

        Assert::same($data, ['id' => 1, 'email' => 'test@email.com', 'balance' => 100.0, 'comments' => []]);
    }

    public function testFetchFields(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@email.com';
        $user->balance = 100;
        $user->comments = [];

        $data = $this->mapper->fetchFields($user);

        Assert::same($data, ['id' => 1, 'email' => 'test@email.com', 'balance' => 100.0]);
    }

    public function testFetchRelationsSkipsPlainProperties(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@email.com';

        Assert::same($this->mapper->fetchRelations($user), []);
    }

    public function testCustomDiscriminatorColumn(): void
    {
        $mapper = new PromiseMapper(Memory::orm(new Schema([
            Human::class => [
                SchemaInterface::ENTITY => Human::class,
                SchemaInterface::MAPPER => PromiseMapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'human',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'birthday', 'kind'],
                SchemaInterface::CHILDREN => ['engineer' => Engineer::class],
                SchemaInterface::DISCRIMINATOR => 'kind',
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ])), Human::class);

        $engineer = $mapper->init(['id' => 1, 'kind' => 'engineer']);

        Assert::instanceOf($engineer, Engineer::class);
        Assert::same($mapper->fetchFields($engineer), ['id' => null, 'birthday' => null, 'kind' => 'engineer']);
    }

    #[BeforeTest]
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
