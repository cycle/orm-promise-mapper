<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Feature\Common\PromiseMapper;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Document;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\ReadonlyIdUser;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\ReadonlyUser;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Uid;
use Cycle\ORM\PromiseMapper\Tests\Support\PromiseMapperTestCase;
use Cycle\ORM\Schema;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class ReadonlyPropertyTest extends PromiseMapperTestCase
{
    #[Test]
    public function testPersistWithReadonlyPropertySetInConstructor(): void
    {
        $user = new ReadonlyUser('new@world.com');

        (new EntityManager($this->orm))->persist($user)->run();

        Assert::same($user->id, 1);
        Assert::same($user->email, 'new@world.com');
    }

    #[Test]
    public function testLoadInitializesReadonlyProperty(): void
    {
        $this->getDatabase()->table('readonly_users')->insertOne(['email' => 'hello@world.com']);

        $user = $this->orm->with(heap: new Heap())->getRepository(ReadonlyUser::class)->findByPK(1);

        Assert::same($user->email, 'hello@world.com');
    }

    #[Test]
    public function testPersistFailsWhenGeneratedIdHitsConstructorSetReadonlyId(): void
    {
        $user = new ReadonlyIdUser('new@world.com');

        Expect::exception(\Error::class)->withMessage('Cannot modify readonly property ' . ReadonlyIdUser::class . '::$id');

        (new EntityManager($this->orm))->persist($user)->run();
    }

    #[Test]
    public function testPersistKeepsConstructorSetReadonlyValueObject(): void
    {
        $document = new Document(new Uid('abc'), 'hello');

        (new EntityManager($this->orm))->persist($document)->run();
        $document->title = 'updated';
        (new EntityManager($this->orm))->persist($document)->run();

        $loaded = $this->orm->with(heap: new Heap())->getRepository(Document::class)->findByPK('abc');
        Assert::same((string) $loaded->id, 'abc');
        Assert::same($loaded->title, 'updated');
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->makeTable('readonly_users', [
            'id' => 'primary',
            'email' => 'string',
        ]);
        $this->makeTable('documents', [
            'id' => 'string',
            'title' => 'string',
        ], pk: ['id']);

        $this->withSchema([
            Document::class => [
                Schema::ROLE => 'document',
                Schema::MAPPER => PromiseMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'documents',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'title'],
                Schema::TYPECAST => ['id' => [Uid::class, 'cast']],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            ReadonlyIdUser::class => [
                Schema::ROLE => 'readonly_id_user',
                Schema::MAPPER => PromiseMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'readonly_users',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            ReadonlyUser::class => [
                Schema::ROLE => 'readonly_user',
                Schema::MAPPER => PromiseMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'readonly_users',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]);
    }
}
