<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Feature\Common\Relation\HasMany;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Comment;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\User;
use Cycle\ORM\PromiseMapper\Tests\Support\PromiseMapperTestCase;
use Cycle\ORM\Reference\Promise;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;

final class HasManyPromiseMapperTest extends PromiseMapperTestCase
{
    #[\Testo\Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'comments' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id' => 2,
                        'user_id' => 1,
                        'message' => 'msg 2',
                    ],
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'comments' => [],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testPromised(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->captureReadQueries();
        \Testo\Assert::instanceOf($u->comments, Promise::class);
        $this->assertNumReads(0);

        /** @var Promise $promise */
        $promise = $u->comments;
        $promise->resolve();
        $this->assertNumReads(1);

        \Testo\Assert::count($promise->getValue(), 3);
        \Testo\Assert::instanceOf($promise->getValue()[0], Comment::class);
    }

    #[\Testo\Test]
    public function testHasManyPromiseLoaded(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->captureReadQueries();
        \Testo\Assert::instanceOf($p = $u->comments, Promise::class);
        $this->assertNumReads(0);

        /** @var Promise $p */
        \Testo\Assert::false($p->hasValue());
        $p->resolve();
        \Testo\Assert::true($p->hasValue());
        \Testo\Assert::array($p->getValue());
    }

    #[\Testo\Test]
    public function testHasManyPromiseRole(): void
    {
        $u = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        /** @var Promise $p */
        $p = $u->comments;

        \Testo\Assert::same($p->getRole(), 'comment');
    }

    #[\Testo\Test]
    public function testHasManyPromiseScope(): void
    {
        $u = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        /** @var Promise $p */
        $p = $u->comments;

        \Testo\Assert::equals($p->getScope(), [
            'user_id' => 1,
        ]);
    }

    #[\Testo\Test]
    public function testPromisedEmpty(): void
    {
        $u = (new Select($this->orm, User::class))->wherePK(2)->fetchOne();

        $this->captureReadQueries();
        \Testo\Assert::instanceOf($p = $u->comments, ReferenceInterface::class);
        $this->assertNumReads(0);
        /** @var Promise $p */
        $p->resolve();
        \Testo\Assert::count($p->getValue(), 0);
        $this->assertNumReads(1);
    }

    #[\Testo\Test]
    public function testNoChanges(): void
    {
        $u = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u);

        $this->assertNumWrites(0);
        $this->assertNumReads(0);
    }

    #[\Testo\Test]
    public function testNoChangesWithNoChildren(): void
    {
        $u = (new Select($this->orm, User::class))->wherePK(2)->fetchOne();

        $this->captureReadQueries();
        $this->captureWriteQueries();
        $this->save($u);

        $this->assertNumWrites(0);
        $this->assertNumReads(0);
    }

    #[\Testo\Test]
    public function testRemoveChildren(): void
    {
        $selector = new Select($this->orm, User::class);

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        /** @var Promise $p */
        $p = $e->comments;
        $e->comments = $p->fetch();
        \Testo\Assert::count($e->comments, 3);

        $e->comments = \array_values(\array_filter($e->comments, static fn(Comment $comment) => $comment->id !== 2));

        $this->save($e);

        /** @var User $e */
        $e = (new Select($this->orm->with(heap: new Heap()), User::class))->wherePK(1)->fetchOne();

        /** @var Promise $p */
        $p = $e->comments;
        $e->comments = $p->fetch();
        \Testo\Assert::count($e->comments, 2);

        \Testo\Assert::same($e->comments[0]->message, 'msg 1');
        \Testo\Assert::same($e->comments[1]->message, 'msg 3');
    }

    #[\Testo\Test]
    public function testAddAndRemoveChildren(): void
    {
        /** @var User $e */
        $e = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        /** @var Promise $p */
        $p = $e->comments;
        $e->comments = $p->fetch();

        $e->comments = \array_values(\array_filter($e->comments, static fn(Comment $comment) => $comment->id !== 2));

        $c = new Comment();
        $c->message = 'msg 4';
        $e->comments[] = $c;

        $this->save($e);

        /** @var User $e */
        $e = (new Select($this->orm->with(heap: new Heap()), User::class))->wherePK(1)->fetchOne();

        /** @var Promise $p */
        $p = $e->comments;
        $e->comments = $p->fetch();

        \Testo\Assert::count($e->comments, 3);
        \Testo\Assert::same($e->comments[0]->message, 'msg 1');
        \Testo\Assert::same($e->comments[1]->message, 'msg 3');
        \Testo\Assert::same($e->comments[2]->message, 'msg 4');
    }

    #[\Testo\Test]
    public function testSliceAndSaveToAnotherParent(): void
    {
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        /** @var Promise $p */
        $p = $a->comments;
        $a->comments = $p->fetch();
        /** @var Promise $p */
        $p = $b->comments;
        $b->comments = $p->fetch();

        \Testo\Assert::count($a->comments, 3);
        \Testo\Assert::count($b->comments, 0);

        $b->comments = \array_slice($a->comments, 0, 2, true);
        foreach ($b->comments as $c) {
            $a->comments = \array_values(\array_filter($a->comments, static fn(Comment $comment) => $comment->id !== $c->id));
        }

        $b->comments[0]->message = 'new b';

        \Testo\Assert::count($a->comments, 1);
        \Testo\Assert::count($b->comments, 2);

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = (new Select($this->orm->with(heap: new Heap()), User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD,
            'as' => 'comment',
        ])->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($a->comments, 1);
        \Testo\Assert::count($b->comments, 2);

        \Testo\Assert::equals($a->comments[0]->id, 3);
        \Testo\Assert::equals($b->comments[0]->id, 1);
        \Testo\Assert::equals($b->comments[1]->id, 2);

        \Testo\Assert::equals($b->comments[0]->message, 'new b');
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string',
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [1, 'msg 3'],
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
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => PromiseMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByIDScope::class,
            ],
        ]));
    }
}
