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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\ContextAwareIdentifiersExtractorInterface;
use ApiPlatform\Core\Api\ContextAwareIriConverterInterface;
use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\OperationDataProviderTrait;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Util\AttributesExtractor;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class IriConverter implements ContextAwareIriConverterInterface
{
    use OperationDataProviderTrait;
    use ResourceClassInfoTrait;

    private $routeNameResolver;
    private $router;
    private $identifiersExtractor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ItemDataProviderInterface $itemDataProvider, RouteNameResolverInterface $routeNameResolver, RouterInterface $router, PropertyAccessorInterface $propertyAccessor = null, IdentifiersExtractorInterface $identifiersExtractor = null, SubresourceDataProviderInterface $subresourceDataProvider = null, IdentifierConverterInterface $identifierConverter = null, ResourceClassResolverInterface $resourceClassResolver = null, $resourceMetadataFactory = null)
    {
        $this->itemDataProvider = $itemDataProvider;
        $this->routeNameResolver = $routeNameResolver;
        $this->router = $router;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->identifierConverter = $identifierConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->identifiersExtractor = $identifiersExtractor ?: new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, $propertyAccessor ?? PropertyAccess::createPropertyAccessor());
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri(string $iri, array $context = [])
    {
        try {
            $parameters = $this->router->match($iri);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('No route matches "%s".', $iri), $e->getCode(), $e);
        }

        if (!isset($parameters['_api_resource_class'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        if (isset($parameters['_api_collection_operation_name'])) {
            throw new InvalidArgumentException(sprintf('The iri "%s" references a collection not an item.', $iri));
        }

        $attributes = AttributesExtractor::extractAttributes($parameters);

        try {
            $identifiers = $this->extractIdentifiers($parameters, $attributes);
        } catch (InvalidIdentifierException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->identifierConverter) {
            $context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] = true;
        }

        if (isset($attributes['subresource_operation_name'])) {
            if (($item = $this->getSubresourceData($identifiers, $attributes, $context)) && !\is_array($item)) {
                return $item;
            }

            throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
        }

        if ($item = $this->getItemData($identifiers, $attributes, $context)) {
            return $item;
        }

        throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string
    {
        $resourceClass = $this->getResourceClass($item, true);

        // TODO: Deprecate the use of ResourceMetadataFactoryInterface
        if (!isset($context['operation_name']) && $this->resourceMetadataFactory instanceof ResourceCollectionMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            [$operationName, $operation] = $resourceMetadata->getFirstOperation();
            if ($operationName) {
                $context['operation_name'] = $operationName;
                $context['identifiers'] = $operation->getIdentifiers();
                $context['has_composite_identifier'] = $operation->getCompositeIdentifier();
            }
        }

        try {
            $identifiers = $this->identifiersExtractor instanceof ContextAwareIdentifiersExtractorInterface ? $this->identifiersExtractor->getIdentifiersFromItem($item, $context) : $this->identifiersExtractor->getIdentifiersFromItem($item);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass), $e->getCode(), $e);
        }

        if (isset($context['operation_name'])) {
            if (\count($identifiers) > 1 && ($context['has_composite_identifier'] ?? false)) {
                $identifiers = [key($context['identifiers']) => CompositeIdentifierParser::stringify($identifiers)];
            }

            return $this->router->generate($context['operation_name'], $identifiers, $this->getReferenceType($resourceClass, $referenceType, $context));
        }

        return $this->getItemIriFromResourceClass($resourceClass, $identifiers, $this->getReferenceType($resourceClass, $referenceType));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = null, array $context = []): string
    {
        if ($context['extra_properties']['is_legacy_subresource'] ?? false) {
            @trigger_error('The IRI will change in 3.0 and match the operation of the resulting resource. Switch to an alternate resource when possible.');
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $operation = $resourceMetadata->getOperation($context['extra_properties']['legacy_subresource_operation_name']);
            if ($operation) {
                $context['operation_name'] = $context['extra_properties']['legacy_subresource_operation_name'];
                $context['identifiers'] = $operation->getIdentifiers();
                $context['has_composite_identifier'] = $operation->getCompositeIdentifier();
            }
        }

        // TODO: Deprecate the use of ResourceMetadataFactoryInterface
        if (!isset($context['operation_name']) && $this->resourceMetadataFactory instanceof ResourceCollectionMetadataFactoryInterface) {
            [$operationName, $operation] = $this->resourceMetadataFactory->create($resourceClass)->getFirstOperation();
            if ($operationName) {
                $context['operation_name'] = $operationName;
                $context['identifiers'] = $operation->getIdentifiers();
                $context['has_composite_identifier'] = $operation->getCompositeIdentifier();
            }
        }

        try {
            return $this->router->generate($context['operation_name'] ?? $this->getRouteName($resourceClass, OperationType::COLLECTION), $context['identifiers_values'] ?? [], $this->getReferenceType($resourceClass, $referenceType, $context));
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     * TODO: remove in 3.0.
     */
    public function getItemIriFromResourceClass(string $resourceClass, array $identifiers, int $referenceType = null): string
    {
        @trigger_error('getItemIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0', \E_USER_DEPRECATED);

        if ($this->resourceMetadataFactory instanceof ResourceCollectionMetadataFactoryInterface) {
            foreach ($this->resourceMetadataFactory->create($resourceClass) as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if ('GET' === $operation->getMethod() && !$operation->isCollection()) {
                        if (\count($identifiers) > 1 && $operation->getCompositeIdentifier()) {
                            $identifiers = [key($operation->getIdentifiers()) => CompositeIdentifierParser::stringify($identifiers)];
                        }

                        return $this->router->generate($operationName, $identifiers, $this->getReferenceType($resourceClass, $referenceType));
                    }
                }
            }

            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass));
        }

        $routeName = $this->getRouteName($resourceClass, OperationType::ITEM);
        $metadata = $this->resourceMetadataFactory->create($resourceClass);

        if (\count($identifiers) > 1 && true === $metadata->getAttribute('composite_identifier', true)) {
            $identifiers = ['id' => CompositeIdentifierParser::stringify($identifiers)];
        }

        try {
            return $this->router->generate($routeName, $identifiers, $this->getReferenceType($resourceClass, $referenceType));
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     * TODO: remove in 3.0.
     */
    public function getSubresourceIriFromResourceClass(string $resourceClass, array $context, int $referenceType = null): string
    {
        @trigger_error('getSubresourceIriFromResourceClass is deprecated since 2.7 and will not be available anymore in 3.0', \E_USER_DEPRECATED);

        try {
            return $this->router->generate($this->routeNameResolver->getRouteName($resourceClass, OperationType::SUBRESOURCE, $context), $context['subresource_identifiers'], $this->getReferenceType($resourceClass, $referenceType));
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }

    private function getReferenceType(string $resourceClass, ?int $referenceType, array $context = []): ?int
    {
        if (null === $referenceType && null !== $this->resourceMetadataFactory) {
            if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                $metadata = $this->resourceMetadataFactory->create($resourceClass);
                $referenceType = $metadata->getAttribute('url_generation_strategy');
            } else {
                // TODO isntanceof
                /** @var ResourceCollection */
                $metadata = $this->resourceMetadataFactory->create($resourceClass);
                // TODO: Add UrlGenerationStrategy  in the metadata
                $referenceType = isset($context['operation_name']) ? null : $metadata[0]->urlGenerationStrategy;
            }
        }

        return $referenceType ?? UrlGeneratorInterface::ABS_PATH;
    }

    private function getRouteName(string $resourceClass, string $operationType)
    {
        return $this->routeNameResolver->getRouteName($resourceClass, $operationType);
    }
}
