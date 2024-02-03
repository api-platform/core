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

use ApiPlatform\Api\ResourceClassResolverInterface as LegacyResourceClassResolverInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\CompositeIdentifierParser;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
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
    private readonly PropertyAccessorInterface $propertyAccessor;

    /**
     * @param LegacyResourceClassResolverInterface|ResourceClassResolverInterface $resourceClassResolver
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, $resourceClassResolver, private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     *
     * TODO: 3.0 identifiers should be stringable?
     */
    public function getIdentifiersFromItem(object $item, ?Operation $operation = null, array $context = []): array
    {
        if (!$this->isResourceClass($this->getObjectClass($item))) {
            return ['id' => $this->propertyAccessor->getValue($item, 'id')];
        }

        if ($operation && $operation->getClass()) {
            return $this->getIdentifiersFromOperation($item, $operation, $context);
        }

        $resourceClass = $this->getResourceClass($item, true);
        $operation ??= $this->resourceMetadataFactory->create($resourceClass)->getOperation(null, false, true);

        return $this->getIdentifiersFromOperation($item, $operation, $context);
    }

    private function getIdentifiersFromOperation(object $item, Operation $operation, array $context = []): array
    {
        if ($operation instanceof HttpOperation) {
            $links = $operation->getUriVariables();
        } elseif ($operation instanceof GraphQlOperation) {
            $links = $operation->getLinks();
        }

        $identifiers = [];
        foreach ($links ?? [] as $link) {
            if (1 < (is_countable($link->getIdentifiers()) ? \count($link->getIdentifiers()) : 0)) {
                $compositeIdentifiers = [];
                foreach ($link->getIdentifiers() as $identifier) {
                    $compositeIdentifiers[$identifier] = $this->getIdentifierValue($item, $link->getFromClass() ?? $operation->getClass(), $identifier, $link->getParameterName());
                }

                $identifiers[$link->getParameterName()] = CompositeIdentifierParser::stringify($compositeIdentifiers);
                continue;
            }

            $parameterName = $link->getParameterName();
            $identifiers[$parameterName] = $this->getIdentifierValue($item, $link->getFromClass() ?? $operation->getClass(), $link->getIdentifiers()[0], $parameterName, $link->getToProperty());
        }

        return $identifiers;
    }

    /**
     * Gets the value of the given class property.
     */
    private function getIdentifierValue(object $item, string $class, string $property, string $parameterName, ?string $toProperty = null): float|bool|int|string
    {
        if ($item instanceof $class) {
            try {
                return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, $property), $parameterName);
            } catch (NoSuchPropertyException $e) {
                throw new RuntimeException('Not able to retrieve identifiers.', $e->getCode(), $e);
            }
        }

        if ($toProperty) {
            return $this->resolveIdentifierValue($this->propertyAccessor->getValue($item, "$toProperty.$property"), $parameterName);
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
    private function resolveIdentifierValue(mixed $identifierValue, string $parameterName): float|bool|int|string
    {
        if (null === $identifierValue) {
            throw new RuntimeException('No identifier value found, did you forget to persist the entity?');
        }

        if (\is_scalar($identifierValue)) {
            return $identifierValue;
        }

        if ($identifierValue instanceof \Stringable) {
            return (string) $identifierValue;
        }

        if ($identifierValue instanceof \BackedEnum) {
            return (string) $identifierValue->value;
        }

        throw new RuntimeException(sprintf('We were not able to resolve the identifier matching parameter "%s".', $parameterName));
    }
}
