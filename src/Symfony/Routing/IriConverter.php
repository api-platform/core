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

namespace ApiPlatform\Symfony\Routing;

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\AttributesExtractor;
use ApiPlatform\Util\ResourceClassInfoTrait;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @experimental
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IriConverter implements IriConverterInterface
{
    use ResourceClassInfoTrait;
    use UriVariablesResolverTrait;

    private $stateProvider;
    private $router;
    private $identifiersExtractor;
    private $resourceMetadataCollectionFactory;
    private $decorated;

    public function __construct(ProviderInterface $stateProvider, RouterInterface $router, IdentifiersExtractorInterface $identifiersExtractor, ResourceClassResolverInterface $resourceClassResolver, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, UriVariablesConverterInterface $uriVariablesConverter = null, \ApiPlatform\Core\Api\IriConverterInterface $decorated)
    {
        $this->stateProvider = $stateProvider;
        $this->router = $router;
        $this->uriVariablesConverter = $uriVariablesConverter;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        // For the ResourceClassInfoTrait
        $this->resourceClassResolver = $resourceClassResolver;
        $this->resourceMetadataFactory = $resourceMetadataCollectionFactory;
        $this->decorated = $decorated;
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

        if (!isset($parameters['_api_resource_class'], $parameters['_api_operation_name'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        $context['operation'] = $operation = $parameters['_api_operation'] = $this->resourceMetadataCollectionFactory->create($parameters['_api_resource_class'])->getOperation($parameters['_api_operation_name']);

        if (
            ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) ||
            ($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false)
        ) {
            return $this->decorated->getItemFromIri($iri, $context);
        }

        if ($operation->isCollection()) {
            throw new InvalidArgumentException(sprintf('The iri "%s" references a collection not an item.', $iri));
        }

        $attributes = AttributesExtractor::extractAttributes($parameters);

        try {
            $identifiers = $this->getOperationIdentifiers($operation, $parameters, $attributes['resource_class']);
        } catch (InvalidIdentifierException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if ($item = $this->stateProvider->provide($attributes['resource_class'], $identifiers, $attributes['operation_name'], $context)) {
            return $item;
        }

        throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, string $operationName = null, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string
    {
        $resourceClass = $this->getResourceClass($item, true);
        $operationName = $context['operation_name'] ?? $operationName;

        // These are special cases were we want to find the related operation
        // `ResourceMetadataCollection::getOperation` retrieves the first safe operation if no $operationName is given
        if (isset($context['operation'])) {
            $operation = $context['operation'];
            if (
                ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) ||
                ($operation->getExtraProperties()['user_defined_uri_template'] ?? false) ||
                ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false) ||
                ($operation->getExtraProperties()['legacy_subresource_behavior'] ?? false) ||
                // When we want the Iri from an object, we don't want the collection uriTemplate, for this we use getIriFromResourceClass
                $operation->isCollection() || $operation instanceof Post
            ) {
                unset($context['operation']);
                $operationName = null;
            }
        }

        try {
            $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);
        } catch (OperationNotFoundException $e) {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resource) {
                foreach ($resource->getOperations() as $name => $operation) {
                    if ($operationName === $name && !$operation->isCollection()) {
                        break 2;
                    }
                }
            }

            if (!isset($operation)) {
                throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass), $e->getCode(), $e);
            }
        }

        try {
            $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($item, $operation->getName(), ['operation' => $operation]);

            return $this->router->generate($operation->getName(), $identifiers, $operation->getUrlGenerationStrategy() ?? $referenceType);
        } catch (RuntimeException|RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResourceClass(string $resourceClass, string $operationName = null, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string
    {
        // TODO: 3.0 remove the condition
        if ($context['extra_properties']['is_legacy_subresource'] ?? false) {
            trigger_deprecation('api-platform/core', '2.7', 'The IRI will change and match the first operation of the resource. Switch to an alternate resource when possible instead of using subresources.');
            $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($context['extra_properties']['legacy_subresource_operation_name']);
        }

        $operation = $context['operation'] ?? null;

        if ($operation) {
            // TODO: 2.7 should we deprecate this behavior ? example : Entity\Foo.php should take it's own operation? As it's a custom operation can we now?
            if ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) {
                trigger_deprecation('api-platform/core', '2.7', 'The IRI will change and match the first operation of the resource. Switch to an alternate resource when possible instead of using subresources.');
                $operationName = $operation->getExtraProperties()['legacy_subresource_operation_name'];
                $operation = null;
            } elseif (!($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false) && ($operation->getExtraProperties()['user_defined_uri_template'] ?? false)) {
                $operation = null;
                $operationName = null;
            }
        }

        try {
            if (!$operation) {
                $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName, $context['force_collection'] ?? true);
            }

            $identifiers = $context['identifiers_values'] ?? [];

            return $this->router->generate($operation->getName(), $identifiers, $operation->getUrlGenerationStrategy() ?? $referenceType);
        } catch (RoutingExceptionInterface|OperationNotFoundException $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for "%s".', $resourceClass), $e->getCode(), $e);
        }
    }
}
