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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Metadata;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class LinkFactory implements LinkFactoryInterface, PropertyLinkFactoryInterface
{
    public function __construct(private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ResourceClassResolverInterface $resourceClassResolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createLinkFromProperty(Metadata $operation, string $property): Link
    {
        $metadata = $this->propertyMetadataFactory->create($resourceClass = $operation->getClass(), $property);
        $relationClass = $this->getPropertyClassType($metadata->getBuiltinTypes());
        if (!$relationClass) {
            throw new RuntimeException(\sprintf('We could not find a class matching the uriVariable "%s" on "%s".', $property, $resourceClass));
        }

        $identifiers = $this->resourceClassResolver->isResourceClass($relationClass) ? $this->getIdentifiersFromResourceClass($relationClass) : ['id'];

        return new Link(fromClass: $relationClass, toProperty: $property, identifiers: $identifiers, parameterName: $property);
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromIdentifiers(Metadata $operation): array
    {
        $identifiers = $this->getIdentifiersFromResourceClass($resourceClass = $operation->getClass());

        if (!$identifiers) {
            return [];
        }

        $link = (new Link())->withFromClass($resourceClass)->withIdentifiers($identifiers);
        $parameterName = $identifiers[0];
        if ('value' === $parameterName && enum_exists($resourceClass)) {
            $parameterName = 'id';
        }

        if (1 < \count($identifiers)) {
            $parameterName = 'id';
            $link = $link->withCompositeIdentifier(true);
        }

        return [$link->withParameterName($parameterName)];
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromRelations(Metadata $operation): array
    {
        $links = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass = $operation->getClass()) as $property) {
            $metadata = $this->propertyMetadataFactory->create($resourceClass, $property);

            if (!($relationClass = $this->getPropertyClassType($metadata->getBuiltinTypes())) || !$this->resourceClassResolver->isResourceClass($relationClass)) {
                continue;
            }

            $identifiers = $this->getIdentifiersFromResourceClass($resourceClass);

            $links[] = (new Link())->withFromProperty($property)->withFromClass($resourceClass)->withToClass($relationClass)->withIdentifiers($identifiers);
        }

        return $links;
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromAttributes(Metadata $operation): array
    {
        $links = [];
        try {
            $reflectionClass = new \ReflectionClass($resourceClass = $operation->getClass());
            foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
                $reflectionProperty = $reflectionClass->getProperty($property);

                foreach ($reflectionProperty->getAttributes(Link::class) as $attributeLink) {
                    $metadata = $this->propertyMetadataFactory->create($resourceClass, $property);

                    $attributeLink = $attributeLink->newInstance()
                        ->withFromProperty($property);

                    if (!$attributeLink->getFromClass()) {
                        $attributeLink = $attributeLink->withFromClass($resourceClass)->withToClass($this->getPropertyClassType($metadata->getBuiltinTypes()) ?? $resourceClass);
                    }

                    $links[] = $attributeLink;
                }
            }
        } catch (\ReflectionException) {
        }

        return $links;
    }

    /**
     * {@inheritdoc}
     */
    public function completeLink(Link $link): Link
    {
        if (!$link->getIdentifiers()) {
            $link = $link->withIdentifiers($this->getIdentifiersFromResourceClass($link->getFromClass()));
        }

        if (1 < \count((array) $link->getIdentifiers())) {
            $link = $link->withCompositeIdentifier(true);
        }

        return $link;
    }

    private function getIdentifiersFromResourceClass(string $resourceClass): array
    {
        $hasIdProperty = false;
        $identifiers = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $isIdentifier = $this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier();

            if (!$hasIdProperty && null === $isIdentifier) {
                $hasIdProperty = 'id' === $property;
            }

            if ($isIdentifier) {
                $identifiers[] = $property;
            }
        }

        if ($hasIdProperty && !$identifiers) {
            return ['id'];
        }

        if (!$hasIdProperty && !$identifiers && enum_exists($resourceClass)) {
            return ['value'];
        }

        return $identifiers;
    }

    /**
     * @param Type[]|null $types
     */
    private function getPropertyClassType(?array $types): ?string
    {
        foreach ($types ?? [] as $type) {
            if ($type->isCollection()) {
                return $this->getPropertyClassType($type->getCollectionValueTypes());
            }

            if ($class = $type->getClassName()) {
                return $class;
            }
        }

        return null;
    }
}
