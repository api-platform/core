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
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\AttributesExtractor;
use ApiPlatform\Util\ClassInfoTrait;
use ApiPlatform\Util\ResourceClassInfoTrait;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IriConverter implements IriConverterInterface
{
    use ClassInfoTrait;
    use ResourceClassInfoTrait;
    use UriVariablesResolverTrait;

    public function __construct(private readonly ProviderInterface $provider, private readonly RouterInterface $router, private readonly IdentifiersExtractorInterface $identifiersExtractor, ResourceClassResolverInterface $resourceClassResolver, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ?UriVariablesConverterInterface $uriVariablesConverter = null, private readonly ?IriConverterInterface $decorated = null)
    {
        $this->resourceClassResolver = $resourceClassResolver;
        $this->uriVariablesConverter = $uriVariablesConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object
    {
        try {
            $parameters = $this->router->match($iri);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('No route matches "%s".', $iri), $e->getCode(), $e);
        }

        $parameters['_api_operation_name'] ??= null;

        if (!isset($parameters['_api_resource_class'], $parameters['_api_operation_name'])) {
            throw new InvalidArgumentException(sprintf('No resource associated to "%s".', $iri));
        }

        $operation = $parameters['_api_operation'] = $this->resourceMetadataCollectionFactory->create($parameters['_api_resource_class'])->getOperation($parameters['_api_operation_name']);

        if ($operation instanceof CollectionOperationInterface) {
            throw new InvalidArgumentException(sprintf('The iri "%s" references a collection not an item.', $iri));
        }

        if (!$operation instanceof HttpOperation) {
            throw new RuntimeException(sprintf('The iri "%s" does not reference an HTTP operation.', $iri));
        }
        $attributes = AttributesExtractor::extractAttributes($parameters);

        try {
            $uriVariables = $this->getOperationUriVariables($operation, $parameters, $attributes['resource_class']);
        } catch (InvalidIdentifierException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if ($item = $this->provider->provide($operation, $uriVariables, $context)) {
            return $item;
        }

        throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResource(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, Operation $operation = null, array $context = []): ?string
    {
        $resourceClass = \is_string($resource) ? $resource : $this->getObjectClass($resource);

        if (!$this->resourceClassResolver->isResourceClass($resourceClass)) {
            return $this->generateSkolemIri($resource, $referenceType, $operation, $context, $resourceClass);
        }

        // This is only for when a class (that is not a resource) extends another one that is a resource, we should remove this behavior
        if (!\is_string($resource)) {
            $resourceClass = $this->getResourceClass($resource, true);
        }

        if (!$operation) {
            $operation = (new Get())->withClass($resourceClass);
        }

        if ($operation instanceof HttpOperation && 301 === $operation->getStatus()) {
            $operation = ($operation instanceof CollectionOperationInterface ? new GetCollection() : new Get())->withClass($operation->getClass());
            unset($context['uri_variables']);
        }

        if ($operation instanceof HttpOperation && HttpOperation::METHOD_POST === $operation->getMethod() && method_exists($operation, 'getItemUriTemplate') && ($itemUriTemplate = $operation->getItemUriTemplate())) {
            $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($itemUriTemplate);
        }

        // In symfony the operation name is the route name, try to find one if none provided
        if (
            !$operation->getName()
            || ($operation instanceof HttpOperation && HttpOperation::METHOD_POST === $operation->getMethod())
        ) {
            $forceCollection = $operation instanceof CollectionOperationInterface;
            try {
                $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation(null, $forceCollection, true);
            } catch (OperationNotFoundException) {
            }
        }

        if (!$operation->getName() || ($operation instanceof HttpOperation && SkolemIriConverter::$skolemUriTemplate === $operation->getUriTemplate())) {
            return $this->generateSkolemIri($resource, $referenceType, $operation, $context, $resourceClass);
        }

        $identifiers = $context['uri_variables'] ?? [];

        if (\is_object($resource)) {
            try {
                $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($resource, $operation);
            } catch (InvalidArgumentException|RuntimeException $e) {
                // We can try using context uri variables if any
                if (!$identifiers) {
                    throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass), $e->getCode(), $e);
                }
            }
        }

        try {
            return $this->router->generate($operation->getName(), $identifiers, $operation->getUrlGenerationStrategy() ?? $referenceType);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass), $e->getCode(), $e);
        }
    }

    private function generateSkolemIri(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, Operation $operation = null, array $context = [], string $resourceClass = null): string
    {
        if (!$this->decorated) {
            throw new InvalidArgumentException(sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass));
        }

        // Use a skolem iri, the route is defined in genid.xml
        return $this->decorated->getIriFromResource($resource, $referenceType, $operation, $context);
    }
}
