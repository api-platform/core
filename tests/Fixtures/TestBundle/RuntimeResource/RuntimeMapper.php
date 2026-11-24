<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use ApiPlatform\Metadata\Exception\ExceptionInterface;
use ApiPlatform\Metadata\IriConverterInterface;

final class RuntimeMapper
{
    public function __construct(private readonly RuntimeResources $resources)
    {
    }

    /**
     * @param list<int>                                                        $ids
     * @param \Closure(string, list<int>): iterable<int, array<string, mixed>> $load raw values by id, references as ids
     *
     * @return list<RuntimeObject>
     */
    public function hydrate(string $name, array $ids, \Closure $load): array
    {
        $rows = $attempted = [];
        $pending = [$name => array_fill_keys($ids, true)];
        for ($depth = 0; $depth < 2 && [] !== $pending; ++$depth) {
            $next = [];
            foreach ($pending as $resource => $resourceIds) {
                $attempted[$resource] = ($attempted[$resource] ?? []) + $resourceIds;
                foreach ($load($resource, array_keys($resourceIds)) as $id => $values) {
                    $rows[$resource][$id] = $values;
                    foreach ($this->references($resource, $values) as [$target, $targetId]) {
                        if (!isset($attempted[$target][$targetId])) {
                            $next[$target][$targetId] = true;
                        }
                    }
                }
            }
            $pending = $next;
        }

        $objects = [];
        $object = function (string $resource, int $id) use (&$objects): RuntimeObject {
            if (!isset($objects[$resource][$id])) {
                $class = (string) $this->resources->classOf($resource);
                $objects[$resource][$id] = new $class();
                $objects[$resource][$id]->id = $id;
            }

            return $objects[$resource][$id];
        };
        $exists = static fn (string $resource, int $id): bool => isset($rows[$resource][$id]) || !isset($attempted[$resource][$id]);

        foreach ($rows as $resource => $byId) {
            foreach ($byId as $id => $values) {
                $instance = $object($resource, $id);
                foreach ($this->resources->all()[$resource]->attributes as $attribute) {
                    $value = $values[$attribute->name] ?? null;
                    if (null !== $target = $attribute->target()) {
                        $targetIds = array_filter((array) $value, static fn (int $targetId): bool => $exists($target, $targetId));
                        $value = array_map(static fn (int $targetId): RuntimeObject => $object($target, $targetId), array_values($targetIds));
                        $value = $attribute->collection ? $value : $value[0] ?? null;
                    }
                    $instance->{$attribute->name} = $value ?? ($attribute->collection ? [] : null);
                }
            }
        }

        return array_values(array_map(static fn (int $id): RuntimeObject => $objects[$name][$id], array_filter($ids, static fn (int $id): bool => isset($rows[$name][$id]))));
    }

    /**
     * @return array<string, mixed> raw values by attribute name, references as ids
     */
    public function extract(RuntimeObject $object): array
    {
        $id = static fn (mixed $value): mixed => $value instanceof RuntimeObject ? $value->id : $value;
        $values = [];
        foreach ($this->resources->get($object::class)->attributes ?? [] as $attribute) {
            $value = $object->{$attribute->name};
            $values[$attribute->name] = $attribute->collection ? array_map($id, (array) $value) : $id($value);
        }

        return $values;
    }

    public static function filterValue(Attribute $attribute, mixed $value, IriConverterInterface $iriConverter): mixed
    {
        if (!\is_string($value)) {
            return null;
        }

        try {
            return match ($attribute->type) {
                AttributeType::Integer => (int) $value,
                AttributeType::Float => (float) $value,
                AttributeType::Boolean => filter_var($value, \FILTER_VALIDATE_BOOL),
                AttributeType::Datetime => false === strtotime($value) ? null : new \DateTimeImmutable($value),
                AttributeType::Reference => ($resource = $iriConverter->getResourceFromIri($value)) instanceof RuntimeObject && $resource->getResource() === $attribute->target() ? $resource->id : null,
                default => $value,
            };
        } catch (ExceptionInterface) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return iterable<array{string, int}>
     */
    private function references(string $resource, array $values): iterable
    {
        foreach ($this->resources->all()[$resource]->attributes as $attribute) {
            if (null !== $target = $attribute->target()) {
                foreach ((array) ($values[$attribute->name] ?? []) as $targetId) {
                    yield [$target, $targetId];
                }
            }
        }
    }
}
