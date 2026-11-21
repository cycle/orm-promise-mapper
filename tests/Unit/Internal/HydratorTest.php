<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Tests\Unit\Internal;

use Cycle\ORM\PromiseMapper\Internal\Hydrator;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator\Base;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator\Child;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator\ReadonlyBase;
use Cycle\ORM\PromiseMapper\Tests\Fixtures\Hydrator\ReadonlyChild;
use Testo\Assert;
use Testo\Core\Exception\SkipTest;
use Testo\Expect;
use Testo\Test;

#[Test]
final class HydratorTest
{
    public function testHydrateReturnsTheSameEntity(): void
    {
        $entity = new Base();

        Assert::same((new Hydrator())->hydrate($entity, ['id' => 1]), $entity);
    }

    public function testHydrateWritesPropertiesOfAnyVisibility(): void
    {
        $entity = (new Hydrator())->hydrate(new Base(), ['id' => 1, 'level' => 'senior', 'secret' => 'xyz']);

        Assert::same($entity->id, 1);
        Assert::same($entity->getLevel(), 'senior');
        Assert::same($entity->getSecret(), 'xyz');
    }

    public function testHydrateWritesPrivatePropertiesOfParentClass(): void
    {
        $entity = (new Hydrator())->hydrate(new Child(), ['id' => 1, 'level' => 'senior', 'secret' => 'xyz']);

        Assert::same($entity->id, 1);
        Assert::same($entity->getLevel(), 'senior');
        Assert::same($entity->getSecret(), 'xyz');
    }

    public function testHydrateInitializesReadonlyProperty(): void
    {
        $entity = (new Hydrator())->hydrate(new Child(), ['name' => 'John']);

        Assert::same($entity->name, 'John');
    }

    public function testHydrateInitializesReadonlyPropertiesOfParentClass(): void
    {
        $entity = (new Hydrator())->hydrate(new ReadonlyChild(), ['src' => '/a.jpg', 'width' => 200, 'secret' => 'xyz']);

        Assert::same($entity->src, '/a.jpg');
        Assert::same($entity->width, 200);
        Assert::same($entity->getSecret(), 'xyz');
    }

    public function testHydrateSkipsReadonlyPropertyHoldingIdenticalValue(): void
    {
        $entity = (new Hydrator())->hydrate(new ReadonlyChild(), ['preset' => 'constructor', 'src' => '/a.jpg']);

        Assert::same($entity->preset, 'constructor');
        Assert::same($entity->src, '/a.jpg');
    }

    public function testHydrateSkipsReadonlyPropertyHoldingEqualObject(): void
    {
        $createdAt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $entity = new class($createdAt) {
            public function __construct(
                public readonly \DateTimeImmutable $createdAt,
            ) {}
        };

        (new Hydrator())->hydrate($entity, ['createdAt' => new \DateTimeImmutable('2026-01-01 00:00:00')]);

        Assert::same($entity->createdAt, $createdAt);
    }

    public function testHydrateThrowsOnDifferentValueForInitializedReadonlyProperty(): void
    {
        Expect::exception(\Error::class)->withMessage('Cannot modify readonly property ' . ReadonlyBase::class . '::$preset');

        (new Hydrator())->hydrate(new ReadonlyChild(), ['preset' => 'data']);
    }

    public function testExtractSkipsVirtualProperties(): void
    {
        \PHP_VERSION_ID >= 80400 or throw new SkipTest('Property hooks need PHP 8.4.');

        // Hook syntax does not parse before PHP 8.4, and the suite also runs on 8.2.
        $entity = eval(<<<'PHP'
            return new class {
                public ?string $stored = null;
                public string $writeOnly {
                    set(string $value) {
                        $this->stored = $value;
                    }
                }
                public string $computed {
                    get => 'computed';
                }
            };
            PHP);
        $hydrator = new Hydrator();
        $hydrator->hydrate($entity, ['writeOnly' => 'value']);

        Assert::same($hydrator->extract($entity), ['stored' => 'value']);
    }

    public function testHydrateAnonymousClass(): void
    {
        $entity = new class {
            private ?string $foo = null;

            public function getFoo(): ?string
            {
                return $this->foo;
            }
        };

        Assert::same((new Hydrator())->hydrate($entity, ['foo' => 'bar'])->getFoo(), 'bar');
    }

    public function testHydrateCoercesScalarsLikeNonStrictAssignment(): void
    {
        $entity = new class {
            public int $int = 0;
            public bool $bool = false;
            public ?float $float = null;
        };

        (new Hydrator())->hydrate($entity, ['int' => '42', 'bool' => 1, 'float' => '10.50']);

        Assert::same($entity->int, 42);
        Assert::same($entity->bool, true);
        Assert::same($entity->float, 10.5);
    }

    public function testHydrateAnonymousClassExtendingEntity(): void
    {
        $entity = new class extends Base {};

        (new Hydrator())->hydrate($entity, ['id' => 1, 'level' => 'senior', 'secret' => 'xyz']);

        Assert::same($entity->id, 1);
        Assert::same($entity->getLevel(), 'senior');
        Assert::same($entity->getSecret(), 'xyz');
    }

    public function testHydrateWritesShadowingPropertyOfChild(): void
    {
        $entity = (new Hydrator())->hydrate(new Child(), ['shadowed' => 'new']);

        Assert::same($entity->getChildShadowed(), 'new');
        Assert::same($entity->getBaseShadowed(), 'base');
    }

    public function testHydrateSkipsUnknownAndStaticProperties(): void
    {
        $entity = (new Hydrator())->hydrate(new Base(), ['id' => 1, 'unknown' => 'value', 'registry' => 'changed']);

        Assert::same($entity->id, 1);
        Assert::false(\property_exists($entity, 'unknown'));
        Assert::same(Base::$registry, 'static');
    }

    public function testExtractReadsPropertiesOfAnyVisibility(): void
    {
        $hydrator = new Hydrator();
        $entity = $hydrator->hydrate(new Base(), ['id' => 1, 'level' => 'senior', 'secret' => 'xyz']);

        Assert::same($hydrator->extract($entity), [
            'id' => 1,
            'level' => 'senior',
            'secret' => 'xyz',
            'shadowed' => 'base',
        ]);
    }

    public function testExtractReadsPrivatePropertiesOfParentClass(): void
    {
        $hydrator = new Hydrator();
        $entity = $hydrator->hydrate(new Child(), ['id' => 1, 'secret' => 'xyz', 'name' => 'John', 'uninitialized' => 5]);

        Assert::same($hydrator->extract($entity), [
            'id' => 1,
            'level' => null,
            'secret' => 'xyz',
            'shadowed' => 'child',
            'name' => 'John',
            'uninitialized' => 5,
        ]);
    }

    public function testExtractSkipsUninitializedProperties(): void
    {
        $data = (new Hydrator())->extract(new Child());

        Assert::false(\array_key_exists('name', $data));
        Assert::false(\array_key_exists('uninitialized', $data));
        Assert::same($data['id'], null);
    }

    public function testExtractTakesShadowedPropertyFromChildEvenIfUninitialized(): void
    {
        $entity = new class extends Base {
            private string $secret;
        };

        Assert::false(\array_key_exists('secret', (new Hydrator())->extract($entity)));
    }

    public function testExtractAnonymousClass(): void
    {
        $entity = new class {
            private string $foo = 'bar';
            private string $bar = 'baz';
        };

        Assert::same((new Hydrator())->extract($entity), ['foo' => 'bar', 'bar' => 'baz']);
    }

    public function testExtractSkipsDynamicAndStaticProperties(): void
    {
        $entity = new \stdClass();
        $entity->dynamic = 1;

        Assert::same((new Hydrator())->extract($entity), []);
        Assert::false(\array_key_exists('registry', (new Hydrator())->extract(new Base())));
    }
}
