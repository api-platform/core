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

namespace ApiPlatform\Symfony\Bundle\Command\Upgrade;

use ApiPlatform\Doctrine\Common\Filter\DateFilterInterface;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\TypeHelper;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Turns the legacy filters declared on a resource into the canonical {@see UpgradeApiFilterParameter}
 * list that the visitor injects as QueryParameters.
 *
 * Properties and strategies are read from each filter's runtime `getDescription()` (the only place
 * that knows what a class-level auto-detecting filter actually targets), then mapped to the canonical
 * filter by {@see UpgradeApiFilterMapper}. A SearchFilter targeting an association cannot be told apart
 * from one targeting a scalar field through the description alone, so the property's native type is
 * resolved to decide whether it maps to an IriFilter.
 *
 * @internal
 */
final class UpgradeApiFilterResolver
{
    /** DateFilter null-management modes carried verbatim into the QueryParameter `filterContext`. */
    private const DATE_NULL_MANAGEMENT = [
        DateFilterInterface::EXCLUDE_NULL,
        DateFilterInterface::INCLUDE_NULL_BEFORE,
        DateFilterInterface::INCLUDE_NULL_AFTER,
        DateFilterInterface::INCLUDE_NULL_BEFORE_AND_AFTER,
    ];

    public function __construct(
        private readonly UpgradeApiFilterMapper $mapper,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
    ) {
    }

    /**
     * @param list<array{filter: FilterInterface, filterClass: class-string, arguments: array<string, mixed>}> $filters
     *                                                                                                                          one entry per `#[ApiFilter]` declaration (keyed by service id upstream so that two
     *                                                                                                                          instances of the same filter class are kept distinct)
     * @param list<FilterInterface>                                                                            $reservedFilters in-place service filters (the resource `filters:` array) whose query keys must
     *                                                                                                                          not be re-migrated: an #[ApiFilter] mapping onto one of these keys would shadow it
     *
     * @throws UpgradeApiFilterCollisionException when two filters map to the same parameter key, or an
     *                                            #[ApiFilter] key collides with an in-place service filter
     *
     * @return list<UpgradeApiFilterParameter>
     */
    public function resolve(string $resourceClass, array $filters, array $reservedFilters = []): array
    {
        $params = [];
        $seenKeys = [];

        foreach ($reservedFilters as $reservedFilter) {
            foreach (array_keys($this->group($reservedFilter->getDescription($resourceClass))) as $reservedKey) {
                $seenKeys[$reservedKey] = true;
            }
        }

        foreach ($filters as ['filter' => $filter, 'filterClass' => $filterClass, 'arguments' => $arguments]) {
            $description = $filter->getDescription($resourceClass);
            // The new overlay filters do not denormalize property names, so a resource whose filtered
            // properties are renamed by a name converter cannot be migrated faithfully — skip it.
            $this->assertNoNameConversion($filter, $description);

            // The mode of a DateFilter (include/exclude null) lives in the constructor `properties` map
            // as the value, never in getDescription(); read it straight from the filter instance.
            $rawProperties = \is_callable([$filter, 'getProperties']) ? ($filter->getProperties() ?? []) : [];

            foreach ($this->group($description) as $key => $info) {
                if (isset($seenKeys[$key])) {
                    throw new UpgradeApiFilterCollisionException($key);
                }
                $seenKeys[$key] = true;

                $isRelation = null !== $info['property'] && $this->isRelation($resourceClass, $info['property']);
                $mapping = $this->mapper->map($filterClass, $info['strategy'], $info['type'], $isRelation);
                // The new filter system infers the property from a plain key, but cannot for a nested
                // (dotted) key, so it must be stated explicitly even when it equals the key.
                $property = $this->explicitProperty($info['property'], $key);

                $mode = null !== $info['property'] ? ($rawProperties[$info['property']] ?? null) : null;
                $filterContext = \is_string($mode) && \in_array($mode, self::DATE_NULL_MANAGEMENT, true) ? $mode : null;

                // Constructor arguments only carry over when the filter is kept as-is (custom or a
                // surviving filter); a remapped filter has a different constructor.
                $filterArguments = $mapping->filterClass === $filterClass ? $arguments : [];

                $params[] = new UpgradeApiFilterParameter(
                    key: $key,
                    filterClass: $mapping->filterClass,
                    property: $property,
                    nativeType: $mapping->nativeType,
                    castToNativeType: $mapping->castToNativeType,
                    filterContext: $filterContext,
                    caseSensitive: $mapping->caseSensitive,
                    arguments: $filterArguments,
                );
            }
        }

        return $params;
    }

    /**
     * Collapses a filter description into logical parameters: operator/array bracket variants
     * (`quantity[gt]`, `quantity[]`) fold into their base property, and the `order[...]` family
     * folds into a single `order[:property]` template.
     *
     * @param array<string, array<string, mixed>> $description
     *
     * @return array<string, array{property: ?string, strategy: ?string, type: ?string}>
     */
    private function group(array $description): array
    {
        $grouped = [];

        foreach ($description as $descKey => $meta) {
            if (str_starts_with($descKey, 'order[')) {
                $grouped['order[:property]'] = ['property' => null, 'strategy' => null, 'type' => null];
                continue;
            }

            // ExistsFilter uses the `exists[property]` query syntax; collapse it to the catch-all template
            // (the bracketed property, name-converted or not, is resolved by the filter at query time).
            if (str_starts_with($descKey, 'exists[')) {
                $grouped['exists[:property]'] = ['property' => null, 'strategy' => null, 'type' => null];
                continue;
            }

            $key = false === ($pos = strpos($descKey, '[')) ? $descKey : substr($descKey, 0, $pos);

            $grouped[$key] ??= [
                'property' => $meta['property'] ?? $key,
                'strategy' => $meta['strategy'] ?? null,
                'type' => $meta['type'] ?? null,
            ];
        }

        return $grouped;
    }

    /**
     * The new overlay filters read the property as-is (no name-converter denormalization the legacy
     * filters did), while the parameter factory normalizes it — so a filtered property renamed by a
     * configured name converter would target the wrong field. Detect it and skip the resource.
     *
     * @param array<string, array<string, mixed>> $description
     *
     * @throws UpgradeApiFilterNameConversionException
     */
    private function assertNoNameConversion(FilterInterface $filter, array $description): void
    {
        $nameConverter = \is_callable([$filter, 'getNameConverter']) ? $filter->getNameConverter() : null;
        if (!$nameConverter instanceof NameConverterInterface) {
            return;
        }

        foreach ($description as $meta) {
            $property = $meta['property'] ?? null;
            if (null === $property) {
                continue;
            }

            $real = implode('.', array_map($nameConverter->denormalize(...), explode('.', (string) $property)));
            if ($real !== $property) {
                throw new UpgradeApiFilterNameConversionException($property);
            }
        }
    }

    private function explicitProperty(?string $property, string $key): ?string
    {
        if (null === $property) {
            return null;
        }

        return ($property !== $key || str_contains($property, '.')) ? $property : null;
    }

    /**
     * A SearchFilter property is a relation when its native type resolves to an API resource class (an
     * object, or a collection of objects). Gating on the resource resolver keeps value objects such as
     * \DateTime — which also resolve to a class — out of the IriFilter mapping.
     */
    private function isRelation(string $resourceClass, string $property): bool
    {
        try {
            $type = $this->propertyMetadataFactory->create($resourceClass, $property)->getNativeType();
        } catch (PropertyNotFoundException) {
            return false;
        }

        if (null === $type) {
            return false;
        }

        $className = TypeHelper::getClassName(TypeHelper::getCollectionValueType($type) ?? $type);

        return null !== $className && $this->resourceClassResolver->isResourceClass($className);
    }
}
