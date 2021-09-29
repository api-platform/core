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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
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
    public function getIdentifiersFromItem($item, string $operationName = null, array $context = []): array
    {
        $identifiers = [];
        $resourceClass = $this->getResourceClass($item, true);
        $operation = $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName);

        foreach ($operation->getUriVariables() ?? [] as $parameterName => $uriVariableDefinition) {
            if (1 < \count($uriVariableDefinition->getIdentifiers())) {
                $compositeIdentifiers = [];
                foreach ($uriVariableDefinition->getIdentifiers() as $identifier) {
                    $compositeIdentifiers[$identifier] = $this->getIdentifierValue($item, $uriVariableDefinition->getTargetClass() ?? $resourceClass, $identifier, $parameterName);
                }

                $identifiers[($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) ? 'id' : $parameterName] = CompositeIdentifierParser::stringify($compositeIdentifiers);
                continue;
            }

            $identifiers[$parameterName] = $this->getIdentifierValue($item, $uriVariableDefinition->getTargetClass(), $uriVariableDefinition->getIdentifiers()[0], $parameterName);
        }

        return $identifiers;
    }

    /**
     * Gets the value of the given class property.
     */
    private function getIdentifierValue($item, string $class, string $property, string $parameterName)
    {
        if ($item instanceof $class) {
            return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, $property), $parameterName);
        }

        $resourceClass = $this->getResourceClass($item, true);
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            $type = $propertyMetadata->getType();
            if (!$type) {
                continue;
            }

            if ($type->getClassName() === $class) {
                return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, "$propertyName.$property"), $parameterName);
            }

            if ($type->isCollection() && ($collectionValueType = $type->getCollectionValueType()) && $collectionValueType->getClassName() === $class) {
                return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, sprintf('%s[0].%s', $propertyName, $property)), $parameterName);
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

        // TODO: php 8 remove method_exists
        if (is_scalar($identifierValue) || method_exists($identifierValue, '__toString') || $identifierValue instanceof \Stringable) {
            return (string) $identifierValue;
        }

        // TODO: remove this in 3.0
        // we could recurse to find correct identifiers until there it is a scalar but this is not really supported and adds a lot of complexity
        // instead we're deprecating this behavior in favor of something that can be transformed to a string
        if ($this->isResourceClass($relatedResourceClass = $this->getObjectClass($identifierValue))) {
            trigger_deprecation('api-platform/core', '2.7', 'Using a resource class as identifier is deprecated, please make this identifier Stringable');
            $relatedOperation = $this->resourceMetadataFactory->create($relatedResourceClass)->getOperation();
            $relatedIdentifiers = $relatedOperation->getUriVariables();
            if (1 === \count($relatedIdentifiers)) {
                $identifierValue = $this->getIdentifierValue($identifierValue, $relatedResourceClass, current($relatedIdentifiers)->getIdentifiers()[0], $parameterName);

                if ($identifierValue instanceof \Stringable || is_scalar($identifierValue) || method_exists($identifierValue, '__toString')) {
                    return (string) $identifierValue;
                }
            }
        }

        throw new RuntimeException(sprintf('We were not able to resolve the identifier matching parameter "%s".', $parameterName));
    }
}
