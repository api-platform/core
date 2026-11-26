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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

final class SubtypeResolver implements SubtypeResolverInterface
{
    /** @var array<string, list<class-string>> */
    private array $subtypesCache = [];
    /** @var array<class-string, true>|null */
    private ?array $resourceClasses = null;

    public function __construct(
        private readonly ClassDiscriminatorResolverInterface $classDiscriminatorResolver,
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function getMapping(string $class): ?ClassDiscriminatorMapping
    {
        return $this->classDiscriminatorResolver->getMappingForClass($class);
    }

    public function getDirectSubtypes(string $class): array
    {
        $subtypes = [];
        foreach ($this->getMapping($class)?->getTypesMapping() ?? [] as $typeValue => $typeClass) {
            if ($typeClass !== $class && is_a($typeClass, $class, true)) {
                $subtypes[(string) $typeValue] = $typeClass;
            }
        }

        return $subtypes;
    }

    public function getSubtypes(string $class): array
    {
        if (isset($this->subtypesCache[$class])) {
            return $this->subtypesCache[$class];
        }

        $subtypes = [];
        foreach ($this->getDirectSubtypes($class) as $subtype) {
            $subtypes[$subtype] = $subtype;
            foreach ($this->getSubtypes($subtype) as $nestedSubtype) {
                $subtypes[$nestedSubtype] = $nestedSubtype;
            }
        }

        return $this->subtypesCache[$class] = array_values($subtypes);
    }

    public function getTypeName(string $class): string
    {
        return $this->getResource($class)?->getShortName() ?? $this->getClassShortName($class);
    }

    public function getTypes(string $class): array
    {
        if (null === $resource = $this->getResource($class)) {
            return [$this->getClassShortName($class)];
        }

        return $resource->getTypes() ?? [(string) $resource->getShortName()];
    }

    private function getResource(string $class): ?ApiResource
    {
        if (null === $this->resourceClasses) {
            $this->resourceClasses = [];
            foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
                $this->resourceClasses[$resourceClass] = true;
            }
        }

        if (!isset($this->resourceClasses[$class])) {
            return null;
        }

        foreach ($this->resourceMetadataCollectionFactory->create($class) as $resource) {
            return $resource;
        }

        return null;
    }

    private function getClassShortName(string $class): string
    {
        return false === ($position = strrpos($class, '\\')) ? $class : substr($class, $position + 1);
    }
}
