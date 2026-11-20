<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Feature\Common\Relation\BelongsTo;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Profile;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\User;
use Cycle\ORM\PromiseMapper\Tests\Support\PromiseMapperTestCase;
use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;

class BelongsToPromiseMapperTest extends PromiseMapperTestCase
{
    #[\Testo\Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'user_id' => 1,
                'image' => 'image.png',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'image' => 'second.png',
            ],
            [
                'id' => 3,
                'user_id' => null,
                'image' => 'third.png',
            ],
        ]);
    }

    #[\Testo\Test]
    public function testFetchPromises(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        [$a, $b, $c] = $selector->fetchAll();

        /** @var Promise $userA */
        $userA = $a->user;
        /** @var Promise $userB */
        $userB = $b->user;

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        \Testo\Assert::instanceOf($aData['user'], Promise::class);
        \Testo\Assert::instanceOf($bData['user'], Promise::class);
        \Testo\Assert::instanceOf($userA, Promise::class);
        \Testo\Assert::instanceOf($userB, Promise::class);
        \Testo\Assert::null($c->user);
        $userA->resolve();
        $userB->resolve();

        \Testo\Assert::instanceOf($userA->fetch(), User::class);
        \Testo\Assert::null($userB->fetch());

        $this->captureReadQueries();
        \Testo\Assert::same($userA->fetch(), $userA->fetch());
        \Testo\Assert::null($userB->fetch());
        $this->assertNumReads(0);

        \Testo\Assert::equals($userA->fetch()->email, 'hello@world.com');
    }

    #[\Testo\Test]
    public function testFetchPromisesFromHeap(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        [$a, $b, $c] = $selector->fetchAll();

        /** @var Promise $userA */
        $userA = $a->user;
        /** @var Promise $userB */
        $userB = $b->user;

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        \Testo\Assert::instanceOf($aData['user'], Promise::class);
        \Testo\Assert::instanceOf($bData['user'], Promise::class);
        \Testo\Assert::instanceOf($a->user, Promise::class);
        \Testo\Assert::instanceOf($b->user, Promise::class);
        \Testo\Assert::null($c->user);

        // warm up
        (new Select($this->orm, User::class))->fetchAll();

        $this->captureReadQueries();
        \Testo\Assert::instanceOf($userA->fetch(), User::class);
        \Testo\Assert::same($userA->fetch(), $userA->getValue());
        $this->assertNumReads(0);

        // invalid object can't be cached
        $this->captureReadQueries();
        \Testo\Assert::null($userB->fetch());
        $this->assertNumReads(1);

        \Testo\Assert::equals($userA->fetch()->email, 'hello@world.com');
    }

    #[\Testo\Test]
    public function testNoWriteOperations(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $p = $selector->wherePK(1)->fetchOne();

        $this->captureWriteQueries();
        $tr = new EntityManager($this->orm);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(0);
    }

    #[\Testo\Test]
    public function testEditPromised(): void
    {
        $p = (new Select($this->orm, Profile::class))
            ->wherePK(1)->fetchOne();
        /** @var Promise $promise */
        $promise = $p->user;
        $promise->fetch()->balance = 400;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $this->save($p);

        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        $p = (new Select($this->orm->with(heap: new Heap()), Profile::class))->wherePK(1)->fetchOne();
        /** @var Promise $promise */
        $promise = $p->user;

        \Testo\Assert::same($promise->fetch()->balance, 400.0);
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('profile', [
            'id' => 'primary',
            'user_id' => 'integer,null',
            'image' => 'string',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
            ],
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
                [2, 'second.png'],
                [null, 'third.png'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => PromiseMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            Profile::class => [
                Schema::ROLE => 'profile',
                Schema::MAPPER => PromiseMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'image'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => true,
                        ],
                    ],
                ],
            ],
        ]));
    }
}
