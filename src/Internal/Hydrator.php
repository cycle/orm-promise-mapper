<?php

declare(strict_types=1);

namespace Cycle\ORM\PromiseMapper\Internal;

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
     * the identical value: the ORM writes back the values it has just stored, a constructor-set one
     * included. A different value for such a property throws, so the entity never drifts from the
     * stored row (e.g. a constructor-set `null` id after the database generated one).
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
                && $property->getValue($entity) === $value) {
                continue;
            }

            $property->setValue($entity, $value);
        }

        return $entity;
    }

    /**
     * Uninitialized and dynamic properties are left out.
     *
     * @return array<non-empty-string, mixed>
     */
    public function extract(object $entity): array
    {
        $result = [];
        foreach ($this->properties($entity::class) as $name => $property) {
            if ($property->isInitialized($entity)) {
                $result[$name] = $property->getValue($entity);
            }
        }

        return $result;
    }

    /**
     * @param class-string $class
     * @return array<non-empty-string, \ReflectionProperty>
     */
    private static function collectProperties(string $class): array
    {
        $seen = [];
        $levels = [];
        // From the entity class up, so a child's property shadows a parent's private one of the same name.
        for ($reflection = new \ReflectionClass($class); $reflection !== false; $reflection = $reflection->getParentClass()) {
            $level = [];
            foreach ($reflection->getProperties() as $property) {
                $name = $property->getName();
                if ($property->isStatic() || $property->class !== $reflection->name || isset($seen[$name])) {
                    continue;
                }

                $seen[$name] = true;
                $level[$name] = $property;
            }
            $levels[] = $level;
        }

        // Parent properties first; a property redeclared in a child comes with the child's.
        return \array_merge(...\array_reverse($levels));
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
