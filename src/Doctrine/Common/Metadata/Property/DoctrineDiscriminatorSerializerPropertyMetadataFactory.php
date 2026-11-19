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

namespace ApiPlatform\Doctrine\Common\Metadata\Property;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactoryInterface;

/**
 * Makes the serializer groups declared on Doctrine inheritance subclasses (JOINED / SINGLE_TABLE)
 * count when deciding whether a relation should be embedded.
 *
 * The link status of a relation is computed by the serializer property metadata factory from the
 * groups declared on the *related* resource class. For a Doctrine inheritance hierarchy the related
 * class is the abstract parent, so groups declared only on a discriminator subclass are never seen
 * and the relation is emitted as an IRI instead of being embedded. This decorator augments the link
 * status using the discriminator map exposed by the Doctrine ClassMetadata, which only the doctrine
 * layer has access to.
 *
 * It runs before the serializer property metadata factory: it sets readableLink/writableLink to true
 * when a subclass declares a matching group and leaves them untouched (null) otherwise, so the
 * serializer factory keeps its default behavior for every other case.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DoctrineDiscriminatorSerializerPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use ResourceClassInfoTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly PropertyMetadataFactoryInterface $decorated,
        private readonly ?SerializerClassMetadataFactoryInterface $serializerClassMetadataFactory = null,
        ?ResourceClassResolverInterface $resourceClassResolver = null,
    ) {
        $this->resourceClassResolver = $resourceClassResolver;
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        if (null === $this->serializerClassMetadataFactory || null === $this->resourceClassResolver) {
            return $propertyMetadata;
        }

        // The link status is only meaningful once at least one serializer group is involved.
        [$normalizationGroups, $denormalizationGroups] = $this->getEffectiveSerializerGroups($options);
        if (null === $normalizationGroups && null === $denormalizationGroups) {
            return $propertyMetadata;
        }

        // Only act when the serializer factory has not already decided to embed the relation.
        if (true === $propertyMetadata->isReadableLink() && true === $propertyMetadata->isWritableLink()) {
            return $propertyMetadata;
        }

        $relatedClass = $this->getClassNameFromProperty($propertyMetadata);
        if (null === $relatedClass || !$this->isResourceClass($relatedClass)) {
            return $propertyMetadata;
        }

        $relatedClass = $this->resourceClassResolver->getResourceClass(null, $relatedClass);

        $subclasses = $this->getDiscriminatorSubclasses($relatedClass);
        if (!$subclasses) {
            return $propertyMetadata;
        }

        $subclassGroups = [];
        foreach ($subclasses as $subclass) {
            $subclassGroups[] = $this->getClassSerializerGroups($subclass);
        }
        $subclassGroups = array_unique(array_merge(...$subclassGroups));

        if (!$subclassGroups) {
            return $propertyMetadata;
        }

        if (null === $propertyMetadata->isReadableLink() && null !== $normalizationGroups && array_intersect($normalizationGroups, $subclassGroups)) {
            $propertyMetadata = $propertyMetadata->withReadableLink(true);
        }

        if (null === $propertyMetadata->isWritableLink() && null !== $denormalizationGroups && array_intersect($denormalizationGroups, $subclassGroups)) {
            $propertyMetadata = $propertyMetadata->withWritableLink(true);
        }

        return $propertyMetadata;
    }

    /**
     * @return class-string[]
     */
    private function getDiscriminatorSubclasses(string $resourceClass): array
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            return [];
        }

        $classMetadata = $manager->getClassMetadata($resourceClass);
        if (!isset($classMetadata->discriminatorMap) || !\is_array($classMetadata->discriminatorMap)) {
            return [];
        }

        return array_values(array_filter(
            $classMetadata->discriminatorMap,
            static fn (string $class): bool => $class !== $resourceClass,
        ));
    }

    /**
     * @return array{0: string[]|null, 1: string[]|null}
     */
    private function getEffectiveSerializerGroups(array $options): array
    {
        if (isset($options['serializer_groups'])) {
            $groups = (array) $options['serializer_groups'];

            return [$groups, $groups];
        }

        if (\array_key_exists('normalization_groups', $options) && \array_key_exists('denormalization_groups', $options)) {
            return [
                null !== $options['normalization_groups'] ? (array) $options['normalization_groups'] : null,
                null !== $options['denormalization_groups'] ? (array) $options['denormalization_groups'] : null,
            ];
        }

        return [null, null];
    }

    /**
     * @return string[]
     */
    private function getClassSerializerGroups(string $class): array
    {
        try {
            $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($class);
        } catch (\InvalidArgumentException) {
            return [];
        }

        $groups = [];
        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $groups[] = $serializerAttributeMetadata->getGroups();
        }

        return $groups ? array_unique(array_merge(...$groups)) : [];
    }
}
