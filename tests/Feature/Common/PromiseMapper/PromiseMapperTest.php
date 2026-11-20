<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Feature\Common\PromiseMapper;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\User;
use Cycle\ORM\PromiseMapper\Tests\Support\PromiseMapperTestCase;
use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class PromiseMapperTest extends PromiseMapperTestCase
{
    #[Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);

        Assert::equals($selector->fetchData(), [
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
        ]);
    }

    #[Test]
    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[Test]
    public function testMakeByRole(): void
    {
        Assert::instanceOf($this->orm->make('user'), User::class);
    }

    #[Test]
    public function testMakeByClass(): void
    {
        Assert::instanceOf($this->orm->make(User::class), User::class);
    }

    #[Test]
    public function testAssertRoleViaClass(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);

        Assert::instanceOf($result[0]->comments, Promise::class);
        Assert::array($result[0]->comments->fetch());
    }

    #[Test]
    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);

        Assert::instanceOf($result->comments, Promise::class);
        Assert::array($result->comments->fetch());
    }

    #[Test]
    public function testFetchSame(): void
    {
        $u = new User();
        $u->email = 'test';
        $u->balance = 100;

        (new EntityManager($this->orm))->persist($u)->run();

        $selector = new Select($this->orm, User::class);
        $result = $selector->orderBy('id', 'DESC')->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, $u->id);
        Assert::equals($result->email, $u->email);
        Assert::equals($result->balance, $u->balance);

        Assert::same($result, $u);
    }

    #[Test]
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

    #[Test]
    public function testWhere(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[Test]
    public function testDelete(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $em = new EntityManager($this->orm);
        $em->delete($result);
        $em->run();

        $selector = new Select($this->orm->with(heap: new Heap()), User::class);
        Assert::null($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, User::class);
        Assert::null($selector->where('id', 2)->fetchOne());

        Assert::false($this->orm->getHeap()->has($result));
    }

    #[Test]
    public function testHeap(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::equals($result->id, 1);

        Assert::true($this->orm->getHeap()->has($result));
        Assert::same($this->orm->getHeap()->get($result)->getStatus(), Node::MANAGED);

        Assert::equals($this->orm->getHeap()->get($result)->getData(), [
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
        ]);
    }

    #[Test]
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

        Assert::equals($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);
    }

    #[Test]
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

        Assert::equals($e->id, 3);
        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm = $this->orm->with(heap: new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        Assert::equals($result->balance, 400);
    }

    #[Test]
    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);
    }

    #[Test]
    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
    }

    #[Test]
    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 2]);

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[Test]
    public function testLoadOverwriteValues(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $u->email = 'test@email.com';
        Assert::same($u->email, 'test@email.com');

        $u2 = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::same($u2->email, 'test@email.com');

        $u3 = $this->orm->with(heap: new Heap())->getRepository(User::class)->findByPK(1);
        Assert::same($u3->email, 'hello@world.com');

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $u4 = $this->orm->with(heap: new Heap())->getRepository(User::class)->findByPK(1);
        Assert::same($u4->email, 'test@email.com');
    }

    #[Test]
    public function testNullableValuesInASndOut(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::equals((float) $u->balance, 100.0);
        $u->balance = 0.0;

        $this->captureWriteQueries();
        $em = new EntityManager($this->orm);
        $em->persist($u);
        $em->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->with(heap: new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::equals((float) $u->balance, 0.0);

        $u->balance = null;

        $this->captureWriteQueries();
        $em = new EntityManager($this->orm);
        $em->persist($u);
        $em->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->with(heap: new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::null($u->balance);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->makeTable(
            'users',
            [
                'id' => 'primary',
                'email' => 'string',
                'balance' => 'float,nullable',
            ],
        );

        $this->makeTable(
            'comments',
            [
                'id' => 'primary',
                'message' => 'string',
                'user_id' => 'int',
            ],
        );

        $this->getDatabase()->table('users')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('comments')->insertMultiple(
            ['message', 'user_id'],
            [
                ['Comment 1', 1],
                ['Comment 2', 1],
            ],
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
                ],
            ),
        );
    }
}
