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

namespace ApiPlatform\Doctrine\Common\Serializer\Mapping\Loader;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * Exposes the Doctrine inheritance discriminator map (ORM and MongoDB ODM) of an API resource as a serializer
 * discriminator map, so the subtypes of a Doctrine hierarchy are read, written and documented as such.
 *
 * A serializer discriminator map declared in the hierarchy always takes precedence.
 *
 * The serializer does not inherit discriminator maps: every class having subclasses in the Doctrine map gets
 * its own map, restricted to itself and its subclasses, so a resource declared in the middle of a hierarchy
 * resolves its own subtypes too.
 *
 * @internal
 */
final class DoctrineDiscriminatorMappingLoader implements LoaderInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
    ) {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (null !== $classMetadata->getClassDiscriminatorMapping()) {
            return false;
        }

        $class = $classMetadata->getName();
        if (!class_exists($class) || null === $manager = $this->managerRegistry->getManagerForClass($class)) {
            return false;
        }

        $doctrineMetadata = $manager->getClassMetadata($class);
        $discriminatorMap = property_exists($doctrineMetadata, 'discriminatorMap') ? $doctrineMetadata->discriminatorMap : null;
        if (!\is_array($discriminatorMap) || !$discriminatorMap || null === $typeProperty = $this->getTypeProperty($doctrineMetadata)) {
            return false;
        }

        $typesMapping = [];
        $hasSubtypes = false;
        foreach ($discriminatorMap as $typeValue => $typeClass) {
            if (!\is_string($typeClass) || !is_a($typeClass, $class, true)) {
                continue;
            }

            $typesMapping[(string) $typeValue] = $typeClass;
            $hasSubtypes = $hasSubtypes || $typeClass !== $class;
        }

        // Leaves do not need a map: their type is resolved from the maps of their parents
        if (!$hasSubtypes) {
            return false;
        }

        // The root of a hierarchy (e.g. an abstract class) is not necessarily part of the Doctrine map
        $hierarchy = array_filter($discriminatorMap, 'is_string');
        for ($hierarchyClass = $class; false !== $hierarchyClass; $hierarchyClass = get_parent_class($hierarchyClass)) {
            $hierarchy[] = $hierarchyClass;
        }
        $hierarchy = array_unique($hierarchy);

        if (!$this->isResourceHierarchy($hierarchy) || $this->hasSerializerDiscriminatorMap($hierarchy)) {
            return false;
        }

        $classMetadata->setClassDiscriminatorMapping(new ClassDiscriminatorMapping($typeProperty, $typesMapping));

        return true;
    }

    private function getTypeProperty(object $doctrineMetadata): ?string
    {
        // ORM: a DiscriminatorColumnMapping object (ORM 3) or an array (ORM 2)
        if (property_exists($doctrineMetadata, 'discriminatorColumn') && null !== $column = $doctrineMetadata->discriminatorColumn) {
            $name = \is_array($column) ? ($column['name'] ?? null) : ($column->name ?? null);

            return \is_string($name) && '' !== $name ? $name : null;
        }

        // MongoDB ODM
        if (property_exists($doctrineMetadata, 'discriminatorField') && \is_string($doctrineMetadata->discriminatorField) && '' !== $doctrineMetadata->discriminatorField) {
            return $doctrineMetadata->discriminatorField;
        }

        return null;
    }

    /**
     * @param list<string> $hierarchy
     */
    private function isResourceHierarchy(array $hierarchy): bool
    {
        foreach ($hierarchy as $class) {
            if ($this->resourceClassResolver->isResourceClass($class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A serializer map declared anywhere in the hierarchy is the explicit API contract: the Doctrine one is ignored.
     *
     * @param list<string> $hierarchy
     */
    private function hasSerializerDiscriminatorMap(array $hierarchy): bool
    {
        foreach ($hierarchy as $class) {
            if (class_exists($class) && (new \ReflectionClass($class))->getAttributes(DiscriminatorMap::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                return true;
            }
        }

        return false;
    }
}
