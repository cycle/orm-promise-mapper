<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\PromiseMapper;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\User;
use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\PromiseMapper\Tests\Traits\TableTrait;

abstract class PromiseMapperTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'users',
            [
                'id' => 'primary',
                'email' => 'string',
                'balance' => 'float,nullable',
            ]
        );

        $this->makeTable(
            'comments',
            [
                'id' => 'primary',
                'message' => 'string',
                'user_id' => 'int',
            ]
        );

        $this->getDatabase()->table('users')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('comments')->insertMultiple(
            ['message', 'user_id'],
            [
                ['Comment 1', 1],
                ['Comment 2', 1],
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    User::class => [
                        Schema::ROLE => 'user',
                        Schema::MAPPER => PromiseMapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'users',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['id', 'email', 'balance'],
                        Schema::TYPECAST => ['id' => 'int', 'balance' => 'float'],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [
                            'comments' => [
                                Relation::TYPE => Relation::HAS_MANY,
                                Relation::TARGET => 'comment',
                                Relation::SCHEMA => [
                                    Relation::CASCADE => true,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user_id',
                                ],
                            ],
                        ],
                    ],
                    'comment' => [
                        Schema::MAPPER => StdMapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'comments',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['id', 'user_id', 'message'],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);

        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                ],
                [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            $selector->fetchData()
        );
    }

    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        $this->assertSame('user', $this->orm->getHeap()->get($result)->getRole());
    }

    public function testMakeByRole(): void
    {
        $this->assertInstanceOf(User::class, $this->orm->make('user'));
    }

    public function testMakeByClass(): void
    {
        $this->assertInstanceOf(User::class, $this->orm->make(User::class));
    }

    public function testAssertRoleViaClass(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertSame('user', $this->orm->getHeap()->get($result)->getRole());
    }

    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);

        $this->assertInstanceOf(Promise::class, $result[0]->comments);
        $this->assertIsArray($result[0]->comments->fetch());
    }

    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);

        $this->assertInstanceOf(Promise::class, $result->comments);
        $this->assertIsArray($result->comments->fetch());
    }

    public function testFetchSame(): void
    {
        $u = new User();
        $u->email = 'test';
        $u->balance = 100;

        (new EntityManager($this->orm))->persist($u)->run();

        $selector = new Select($this->orm, User::class);
        $result = $selector->orderBy('id', 'DESC')->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($u->id, $result->id);
        $this->assertEquals($u->email, $result->email);
        $this->assertEquals($u->balance, $result->balance);

        $this->assertSame($u, $result);
    }

    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->captureWriteQueries();

        $em = new EntityManager($this->orm);
        $em->persist($result);
        $em->run();
        $this->assertNumWrites(0);
    }

    public function testWhere(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testDelete(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $em = new EntityManager($this->orm);
        $em->delete($result);
        $em->run();

        $selector = new Select($this->orm->with(heap: new Heap()), User::class);
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, User::class);
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $this->assertFalse($this->orm->getHeap()->has($result));
    }

    public function testHeap(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertEquals(1, $result->id);

        $this->assertTrue($this->orm->getHeap()->has($result));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($result)->getStatus());

        $this->assertEquals(
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
            ],
            $this->orm->getHeap()->get($result)->getData()
        );
    }

    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $this->captureWriteQueries();

        $em = new EntityManager($this->orm);
        $em->persist($e);
        $em->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $em = new EntityManager($this->orm);
        $em->persist($e);
        $em->run();
        $this->assertNumWrites(0);

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());
    }

    public function testStoreWithUpdate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $this->captureWriteQueries();

        $em = new EntityManager($this->orm);
        $em->persist($e);

        $e->balance = 400;

        $em->run();

        $this->assertNumWrites(1);

        $this->assertEquals(3, $e->id);
        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->orm = $this->orm->with(heap: new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        $this->assertEquals(400, $result->balance);
    }

    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 2]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testLoadOverwriteValues(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $u->email = 'test@email.com';
        $this->assertSame('test@email.com', $u->email);

        $u2 = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertSame('hello@world.com', $u2->email);

        $u3 = $this->orm->with(heap: new Heap())->getRepository(User::class)->findByPK(1);
        $this->assertSame('hello@world.com', $u3->email);

        $this->captureWriteQueries();
        $em = new EntityManager($this->orm);
        $em->persist($u);
        $em->run();
        $this->assertNumWrites(0);

        $u4 = $this->orm->with(heap: new Heap())->getRepository(User::class)->findByPK(1);
        $this->assertSame('hello@world.com', $u4->email);
    }

    public function testNullableValuesInASndOut(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertEquals(100.0, (float) $u->balance);
        $u->balance = 0.0;

        $this->captureWriteQueries();
        $em = new EntityManager($this->orm);
        $em->persist($u);
        $em->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->with(heap: new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertEquals(0.0, (float) $u->balance);

        $u->balance = null;

        $this->captureWriteQueries();
        $em = new EntityManager($this->orm);
        $em->persist($u);
        $em->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->with(heap: new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertNull($u->balance);
    }
}
