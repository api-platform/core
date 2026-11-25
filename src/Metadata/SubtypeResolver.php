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

use ApiPlatform\Metadata\Exception\RuntimeException;
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
    /** @var array<class-string, ApiSubtype> */
    private array $subtypes = [];
    private bool $typeNamesValidated = false;

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
        if (null !== $resourceShortName = $this->getResourceShortName($class)) {
            return $resourceShortName;
        }

        $this->validateTypeNames();

        return $this->getSubtype($class)->shortName ?? '';
    }

    public function getTypes(string $class): array
    {
        if (null !== $resourceShortName = $this->getResourceShortName($class)) {
            return [$resourceShortName];
        }

        $this->validateTypeNames();

        return $this->getSubtype($class)->types ?? [];
    }

    private function getResourceShortName(string $class): ?string
    {
        if (!isset($this->getResourceClasses()[$class])) {
            return null;
        }

        foreach ($this->resourceMetadataCollectionFactory->create($class) as $resource) {
            return $resource->getShortName();
        }

        return null;
    }

    /**
     * @return array<class-string, true>
     */
    private function getResourceClasses(): array
    {
        if (null === $this->resourceClasses) {
            $this->resourceClasses = [];
            foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
                $this->resourceClasses[$resourceClass] = true;
            }
        }

        return $this->resourceClasses;
    }

    private function getSubtype(string $class): ApiSubtype
    {
        if (isset($this->subtypes[$class])) {
            return $this->subtypes[$class];
        }

        $subtype = ((new \ReflectionClass($class))->getAttributes(ApiSubtype::class)[0] ?? null)?->newInstance() ?? new ApiSubtype();
        $shortName = $subtype->shortName ?? (false === ($position = strrpos($class, '\\')) ? $class : substr($class, $position + 1));

        return $this->subtypes[$class] = new ApiSubtype($shortName, $subtype->types ?? [$shortName]);
    }

    /**
     * @throws RuntimeException when a subtype name or type is also the one of a resource or of another subtype
     */
    private function validateTypeNames(): void
    {
        if ($this->typeNamesValidated) {
            return;
        }

        $owners = [];
        foreach ($this->getResourceClasses() as $resourceClass => $_) {
            foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resource) {
                $owners[$resource->getShortName()] ??= $resourceClass;
            }
        }

        foreach ($this->getResourceClasses() as $resourceClass => $_) {
            foreach ($this->getSubtypes($resourceClass) as $subtype) {
                if (isset($this->resourceClasses[$subtype])) {
                    continue;
                }

                $apiSubtype = $this->getSubtype($subtype);
                foreach (array_unique([$apiSubtype->shortName, ...$apiSubtype->types]) as $name) {
                    if (($owners[$name] ??= $subtype) !== $subtype) {
                        throw new RuntimeException(\sprintf('The subtype "%s" and "%s" are both named "%s" in JSON-LD and Hydra. Give the subtype another name with #[%s(shortName: \'...\')].', $subtype, $owners[$name], $name, ApiSubtype::class));
                    }
                }
            }
        }

        $this->typeNamesValidated = true;
    }
}
