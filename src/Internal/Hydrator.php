<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Internal;

use Cycle\ORM\Heap\Node;

/**
 * Copies data between arrays and entity properties of any visibility, private properties of parent
 * classes included. Static properties are never touched.
 *
 * Writes go through {@see \ReflectionProperty::setValue()}, not an assignment in a scope-bound closure:
 * it coerces scalars the way a non-strict assignment does, so a `'1'` from the database still lands in
 * an `int` property, and it initializes a readonly property from outside the declaring class.
 *
 * @internal
 */
final class Hydrator
{
    /** @var array<class-string, array<non-empty-string, \ReflectionProperty>> */
    private array $properties = [];

    /**
     * Keys without a matching property are skipped, and so are readonly properties that already hold
     * an equal value: the ORM writes back the values it has just stored, a constructor-set one
     * included, and a value typecast from `RETURNING` comes back as a new instance. Equality is the
     * ORM's own change detection ({@see Node::compare()}), so what it would not store as a change is
     * not a conflict here. A different value throws, so the entity never drifts from the stored row
     * (e.g. a constructor-set `null` id after the database generated one).
     *
     * @template T of object
     * @param T $entity
     * @return T
     * @throws \Error When a readonly property already holds a different value.
     */
    public function hydrate(object $entity, array $data): object
    {
        $properties = $this->properties($entity::class);
        foreach ($data as $name => $value) {
            $property = $properties[$name] ?? null;
            if ($property === null || $property->isReadOnly() && $property->isInitialized($entity)
                && Node::compare($property->getValue($entity), $value) === 0) {
                continue;
            }

            $property->setValue($entity, $value);
        }

        return $entity;
    }

    /**
     * Uninitialized, virtual and dynamic properties are left out: none of them holds stored state.
     *
     * @return array<non-empty-string, mixed>
     */
    public function extract(object $entity): array
    {
        $result = [];
        foreach ($this->properties($entity::class) as $name => $property) {
            if ($property->isInitialized($entity) && !self::isVirtual($property)) {
                $result[$name] = $property->getValue($entity);
            }
        }

        return $result;
    }

    /**
     * Virtual properties exist since PHP 8.4, and so does the method telling them apart; the version
     * gate has no mutant a single PHP version could kill.
     *
     * @infection-ignore-all
     */
    private static function isVirtual(\ReflectionProperty $property): bool
    {
        return \PHP_VERSION_ID >= 80400 && $property->isVirtual();
    }

    /**
     * @param class-string $class
     * @return array<non-empty-string, \ReflectionProperty>
     */
    private static function collectProperties(string $class): array
    {
        $hierarchy = [];
        for ($reflection = new \ReflectionClass($class); $reflection !== false; $reflection = $reflection->getParentClass()) {
            $hierarchy[] = $reflection;
        }

        $properties = [];
        // From the root class down: a child's property replaces a parent's private one of the same name
        // and keeps its position, so the order is parent-first like the object's own layout.
        foreach (\array_reverse($hierarchy) as $reflection) {
            foreach ($reflection->getProperties() as $property) {
                if (!$property->isStatic() && $property->class === $reflection->name) {
                    $properties[$property->getName()] = $property;
                }
            }
        }

        return $properties;
    }

    /**
     * Dropping the cache changes nothing a test can observe, only the cost.
     *
     * @infection-ignore-all
     * @param class-string $class
     * @return array<non-empty-string, \ReflectionProperty>
     */
    private function properties(string $class): array
    {
        return $this->properties[$class] ??= self::collectProperties($class);
    }
}
