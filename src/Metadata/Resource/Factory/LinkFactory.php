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

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class LinkFactory implements LinkFactoryInterface
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceClassResolver;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromIdentifiers($operation): array
    {
        $identifiers = $this->getIdentifiersFromResourceClass($resourceClass = $operation->getClass());

        if (!$identifiers) {
            return [];
        }

        $link = (new Link())->withFromClass($resourceClass)->withIdentifiers($identifiers);
        $parameterName = $identifiers[0];

        if (1 < \count($identifiers)) {
            $parameterName = 'id';
            $link = $link->withCompositeIdentifier(true);
        }

        return [$link->withParameterName($parameterName)];
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromRelations($operation): array
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
    public function createLinksFromAttributes($operation): array
    {
        if (\PHP_VERSION_ID < 80000) {
            return [];
        }

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
        } catch (\ReflectionException $e) {
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

        if (1 < \count($link->getIdentifiers())) {
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

        return $identifiers;
    }

    /**
     * @param Type[]|null $types
     */
    private function getPropertyClassType(?array $types): ?string
    {
        foreach ($types ?? [] as $type) {
            if ($type->isCollection()) {
                return $this->getPropertyClassType(method_exists(Type::class, 'getCollectionValueTypes') ? $type->getCollectionValueTypes() : ($type->getCollectionValueType() ? [$type->getCollectionValueType()] : null));
            }

            if ($class = $type->getClassName()) {
                return $class;
            }
        }

        return null;
    }
}
