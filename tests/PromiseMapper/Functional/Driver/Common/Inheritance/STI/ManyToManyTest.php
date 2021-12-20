<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Functional\Driver\Common\Inheritance\STI;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Inheritance\RbacItemAbstract;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Inheritance\RbacPermission;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Inheritance\RbacRole;
use stdClass;

abstract class ManyToManyTest extends StiBaseTest
{
    protected const PARENT_MAPPER = PromiseMapper::class;
    protected const CHILD_MAPPER = StdMapper::class;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('rbac_item', [
            'name' => 'string,primary',
            'description' => 'string,nullable',
            '_type' => 'string,nullable',
        ]);

        $this->makeTable('rbac_item_inheritance', [
            'id' => 'primary',
            'parent' => 'string',
            'child' => 'string',
        ]);

        $this->makeFK('rbac_item_inheritance', 'parent', 'rbac_item', 'name', 'NO ACTION', 'NO ACTION');
        $this->makeFK('rbac_item_inheritance', 'child', 'rbac_item', 'name', 'NO ACTION', 'NO ACTION');

        $this->withSchema(new Schema($this->getSchemaArray()));
    }

    public function testStore(): void
    {
        $role = new RbacRole('superAdmin');

        $permission = new RbacPermission('writeUser');

        $role->children[] = $permission;
        $permission->parents[] = $role;

        $this->save($role);

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm->with(heap: new Heap()), 'rbac_item'))
            ->load('children')
            ->wherePK('superAdmin')->fetchOne();

        self::assertInstanceOf(RbacRole::class, $fetchedRole);
        self::assertCount(1, $fetchedRole->children);
        self::assertInstanceOf(RbacPermission::class, $fetchedRole->children[0]);
        self::assertSame('writeUser', $fetchedRole->children[0]->name);
    }

    public function testClearAndFillRelation(): void
    {
        $role = new RbacRole('superAdmin');
        $role->description = 'admin';
        $permission = new RbacPermission('writeUser');
        $permission->description = 'premission';

        $role->children[] = $permission;
        $permission->parents[] = $role;

        $this->save($role);

        unset($role, $permission);

        $this->orm = $this->orm->with(heap: new Heap());

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm, 'rbac_item'))
            ->load('children')
            ->wherePK('superAdmin')->fetchOne();
        /** @var RbacPermission $fetchedPermission */
        $fetchedPermission = (new Select($this->orm, 'rbac_item'))
            ->load('parents')
            ->wherePK('writeUser')->fetchOne();

        $fetchedRole->children = array_values(
            array_filter($fetchedRole->children, fn(RbacPermission $perm) => $perm->name !== 'writeUser')
        );
        $fetchedPermission->parents = array_values(
            array_filter($fetchedRole->children, fn(RbacRole $role) => $role->name !== 'superAdmin')
        );

        $this->save($fetchedRole);

        $fetchedRole->children[] = $fetchedPermission;
        // Should be solved with proxy task
        $fetchedPermission->parents[] = $fetchedRole;

        $this->save($fetchedRole);

        self::assertTrue(true);
    }

    public function testMakeEntityUsingRole(): void
    {
        $this->assertInstanceOf(RbacRole::class, $this->orm->make('rbac_role'));
        $this->assertInstanceOf(RbacPermission::class, $this->orm->make('rbac_permission'));
        $this->assertInstanceOf(stdClass::class, $this->orm->make('rbac_item_inheritance'));
    }

    public function testMakeUndefinedChildRole(): void
    {
        $mapper = $this->orm->getMapper('rbac_item');

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessageMatches('/`some_undefined_role`.+not found/');

        $mapper->init([], 'some_undefined_role');
    }

    public function testNotTriggersRehydrate(): void
    {
        $role = new RbacRole('superAdmin', 'description');

        $permission = new RbacPermission('writeUser');

        $role->children[] = $permission;
        $permission->parents[] = $role;

        $this->save($role);

        unset($role, $permission);

        $this->orm = $this->orm->with(heap: new Heap());

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm, 'rbac_item'))
            ->wherePK('superAdmin')->load('children')->fetchOne();
        /** @var RbacPermission $fetchedPermission */
        $fetchedPermission = (new Select($this->orm, 'rbac_item'))
            ->wherePK('writeUser')->load('parents')->fetchOne();

        $fetchedRole->description = 'updated description';

        // unlink
        $fetchedRole->children[] = $fetchedPermission;
        $fetchedPermission->parents[] = $fetchedRole;

        self::assertSame('updated description', $fetchedRole->description);

        $this->orm = $this->orm->with(heap: new Heap());
    }

    protected function getSchemaArray(): array
    {
        return [
            RbacItemAbstract::class => [
                SchemaInterface::ROLE => 'rbac_item',
                SchemaInterface::CHILDREN => [
                    'role' => RbacRole::class,
                    'permission' => RbacPermission::class,
                ],
                SchemaInterface::MAPPER => static::PARENT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'rbac_item',
                SchemaInterface::PRIMARY_KEY => 'name',
                SchemaInterface::COLUMNS => ['name', 'description', '_type'],
                SchemaInterface::RELATIONS => [
                    'parents' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'rbac_item',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'rbac_item_inheritance',
                            Relation::INNER_KEY => 'name',
                            Relation::OUTER_KEY => 'name',
                            Relation::THROUGH_INNER_KEY => 'child',
                            Relation::THROUGH_OUTER_KEY => 'parent',
                            Relation::INVERSION => 'children',
                        ],
                    ],
                    'children' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'rbac_item',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'rbac_item_inheritance',
                            Relation::INNER_KEY => 'name',
                            Relation::OUTER_KEY => 'name',
                            Relation::THROUGH_INNER_KEY => 'parent',
                            Relation::THROUGH_OUTER_KEY => 'child',
                            Relation::INVERSION => 'parents',
                        ],
                    ],
                ],
            ],
            RbacRole::class => [
                SchemaInterface::ROLE => 'rbac_role',
            ],
            RbacPermission::class => [
                SchemaInterface::ROLE => 'rbac_permission',
            ],
            'rbac_item_inheritance' => [
                SchemaInterface::ROLE => 'rbac_item_inheritance',
                SchemaInterface::MAPPER => static::CHILD_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'rbac_item_inheritance',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'parent', 'child'],
                SchemaInterface::RELATIONS => [],
            ],
        ];
    }
}
