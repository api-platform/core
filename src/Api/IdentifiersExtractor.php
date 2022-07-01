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

namespace ApiPlatform\Api;

use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * {@inheritdoc}
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IdentifiersExtractor implements IdentifiersExtractorInterface
{
    use ResourceClassInfoTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $propertyAccessor;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     *
     * TODO: 3.0 identifiers should be stringable?
     */
    public function getIdentifiersFromItem($item, Operation $operation = null, array $context = []): array
    {
        $identifiers = [];

        if (!$this->isResourceClass($this->getObjectClass($item))) {
            return ['id' => $this->propertyAccessor->getValue($item, 'id')];
        }

        $resourceClass = $this->getResourceClass($item, true);
        $operation = $operation ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation(null, false, true);

        if ($operation instanceof HttpOperation) {
            $links = $operation->getUriVariables();
        } elseif ($operation instanceof GraphQlOperation) {
            $links = $operation->getLinks();
        }

        foreach ($links ?? [] as $link) {
            if (1 < \count($link->getIdentifiers())) {
                $compositeIdentifiers = [];
                foreach ($link->getIdentifiers() as $identifier) {
                    $compositeIdentifiers[$identifier] = $this->getIdentifierValue($item, $link->getFromClass() ?? $resourceClass, $identifier, $link->getParameterName());
                }

                $identifiers[($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) ? 'id' : $link->getParameterName()] = CompositeIdentifierParser::stringify($compositeIdentifiers);
                continue;
            }

            $identifiers[$link->getParameterName()] = $this->getIdentifierValue($item, $link->getFromClass(), $link->getIdentifiers()[0], $link->getParameterName());
        }

        return $identifiers;
    }

    /**
     * Gets the value of the given class property.
     *
     * @param mixed $item
     */
    private function getIdentifierValue($item, string $class, string $property, string $parameterName)
    {
        if ($item instanceof $class) {
            return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, $property), $parameterName);
        }

        $resourceClass = $this->getResourceClass($item, true);
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            $types = $propertyMetadata->getBuiltinTypes();
            if (null === ($type = $types[0] ?? null)) {
                continue;
            }

            try {
                if ($type->isCollection()) {
                    $collectionValueType = $type->getCollectionValueTypes()[0] ?? null;

                    if (null !== $collectionValueType && $collectionValueType->getClassName() === $class) {
                        return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, sprintf('%s[0].%s', $propertyName, $property)), $parameterName);
                    }
                }

                if ($type->getClassName() === $class) {
                    return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, "$propertyName.$property"), $parameterName);
                }
            } catch (NoSuchPropertyException $e) {
                throw new RuntimeException('Not able to retrieve identifiers.', $e->getCode(), $e);
            }
        }

        throw new RuntimeException('Not able to retrieve identifiers.');
    }

    /**
     * TODO: in 3.0 this method just uses $identifierValue instanceof \Stringable and we remove the weird behavior.
     *
     * @param mixed|\Stringable $identifierValue
     */
    private function resolveIdentifierValue($identifierValue, string $parameterName)
    {
        if (null === $identifierValue) {
            throw new RuntimeException('No identifier value found, did you forgot to persist the entity?');
        }

        if (\is_scalar($identifierValue)) {
            return $identifierValue;
        }

        // TODO: php 8 remove method_exists
        if (method_exists($identifierValue, '__toString') || $identifierValue instanceof \Stringable) {
            return (string) $identifierValue;
        }

        // TODO: remove this in 3.0
        // we could recurse to find correct identifiers until there it is a scalar but this is not really supported and adds a lot of complexity
        // instead we're deprecating this behavior in favor of something that can be transformed to a string
        if ($this->isResourceClass($relatedResourceClass = $this->getObjectClass($identifierValue))) {
            trigger_deprecation('api-platform/core', '2.7', 'Using a resource class as identifier is deprecated, please make this identifier Stringable');
            $relatedOperation = $this->resourceMetadataFactory->create($relatedResourceClass)->getOperation();

            $relatedLinks = [];
            if ($relatedOperation instanceof GraphQlOperation) {
                $relatedLinks = $relatedOperation->getLinks();
            } elseif ($relatedOperation instanceof HttpOperation) {
                $relatedLinks = $relatedOperation->getUriVariables();
            }

            if (1 === \count($relatedLinks)) {
                $identifierValue = $this->getIdentifierValue($identifierValue, $relatedResourceClass, current($relatedLinks)->getIdentifiers()[0], $parameterName);

                if (\is_scalar($identifierValue)) {
                    return $identifierValue;
                }

                if ($identifierValue instanceof \Stringable || method_exists($identifierValue, '__toString')) {
                    return (string) $identifierValue;
                }
            }
        }

        throw new RuntimeException(sprintf('We were not able to resolve the identifier matching parameter "%s".', $parameterName));
    }
}
