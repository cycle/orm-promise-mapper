<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Feature\Common\PromiseMapper;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\TypedUser;
use Cycle\ORM\PromiseMapper\Tests\Support\PromiseMapperTestCase;
use Cycle\ORM\Schema;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class UninitializedPropertyTest extends PromiseMapperTestCase
{
    #[Test]
    public function testPersistWithUninitializedPrimaryKey(): void
    {
        $user = new TypedUser();
        $user->email = 'new@world.com';

        (new EntityManager($this->orm))->persist($user)->run();

        Assert::same($user->id, 1);

        $loaded = $this->orm->with(heap: new Heap())->getRepository(TypedUser::class)->findByPK(1);
        Assert::same($loaded->email, 'new@world.com');
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->makeTable('typed_users', [
            'id' => 'primary',
            'email' => 'string',
        ]);

        $this->withSchema([
            TypedUser::class => [
                Schema::ROLE => 'typed_user',
                Schema::MAPPER => PromiseMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'typed_users',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]);
    }
}
