<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Unit\PromiseMapper;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\User;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use PHPUnit\Framework\TestCase;

class PromiseMapperTest extends TestCase
{
    private PromiseMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $orm = new ORM(
            $this->createMock(FactoryInterface::class),
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
            ])
        );

        $this->mapper = new PromiseMapper($orm, User::class);
    }

    public function testInit(): void
    {
        $this->assertInstanceOf(
            User::class,
            $this->mapper->init(['id' => 1, 'email' => 'test@email.com', 'balance' => 100], User::class)
        );
    }

    public function testInitClassNotExist(): void
    {
        $this->expectException(SchemaException::class);

        $this->assertInstanceOf(
            User::class,
            $this->mapper->init(['id' => 1, 'email' => 'test@email.com', 'balance' => 100], 'foo')
        );
    }

    public function testHydrate(): void
    {
        $user = $this->mapper->hydrate(new User(), ['id' => 1, 'email' => 'test@email.com', 'balance' => 100]);

        $this->assertSame(1, $user->id);
        $this->assertSame('test@email.com', $user->email);
        $this->assertSame(100.0, $user->balance);
    }

    public function testExtract(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@email.com';
        $user->balance = 100;
        $user->comments = [];

        $data = $this->mapper->extract($user);

        $this->assertSame(['id' => 1, 'email' => 'test@email.com', 'balance' => 100.0, 'comments' => []], $data);
    }

    public function testFetchFields(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@email.com';
        $user->balance = 100;
        $user->comments = [];

        $data = $this->mapper->extract($user);

        $this->assertSame(['id' => 1, 'email' => 'test@email.com', 'balance' => 100.0, 'comments' => []], $data);
    }
}
