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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UriVariablesConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\AttributesExtractor;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
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

    private array $localOperationCache = [];
    private array $localIdentifiersExtractorOperationCache = [];

    public function __construct(private readonly ProviderInterface $provider, private readonly RouterInterface $router, private readonly IdentifiersExtractorInterface $identifiersExtractor, ResourceClassResolverInterface $resourceClassResolver, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ?UriVariablesConverterInterface $uriVariablesConverter = null, private readonly ?IriConverterInterface $decorated = null, private readonly ?OperationMetadataFactoryInterface $operationMetadataFactory = null)
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
            throw new InvalidArgumentException(\sprintf('No route matches "%s".', $iri), $e->getCode(), $e);
        }

        $parameters['_api_operation_name'] ??= null;

        if (!isset($parameters['_api_resource_class'], $parameters['_api_operation_name'])) {
            throw new InvalidArgumentException(\sprintf('No resource associated to "%s".', $iri));
        }

        // uri_variables come from the Request context and may not be available
        foreach ($context['uri_variables'] ?? [] as $key => $value) {
            if (!isset($parameters[$key]) || $parameters[$key] !== (string) $value) {
                throw new InvalidArgumentException(\sprintf('The iri "%s" does not reference the correct resource.', $iri));
            }
        }

        if ($operation && !is_a($parameters['_api_resource_class'], $operation->getClass(), true)) {
            throw new InvalidArgumentException(\sprintf('The iri "%s" does not reference the correct resource.', $iri));
        }

        $operation = $parameters['_api_operation'] = $this->resourceMetadataCollectionFactory->create($parameters['_api_resource_class'])->getOperation($parameters['_api_operation_name']);

        if ($operation instanceof CollectionOperationInterface) {
            throw new InvalidArgumentException(\sprintf('The iri "%s" references a collection not an item.', $iri));
        }

        if (!$operation instanceof HttpOperation) {
            throw new RuntimeException(\sprintf('The iri "%s" does not reference an HTTP operation.', $iri));
        }
        $attributes = AttributesExtractor::extractAttributes($parameters);

        try {
            $uriVariables = $this->getOperationUriVariables($operation, $parameters, $attributes['resource_class']);
        } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if ($item = $this->provider->provide($operation, $uriVariables, $context)) {
            return $item;
        }

        throw new ItemNotFoundException(\sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResource(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = []): string
    {
        $resourceClass = $context['force_resource_class'] ?? (\is_string($resource) ? $resource : $this->getObjectClass($resource));

        if ($this->operationMetadataFactory && isset($context['item_uri_template'])) {
            $operation = $this->operationMetadataFactory->create($context['item_uri_template']);
        }

        $localOperationCacheKey = ($operation?->getName() ?? '').$resourceClass.((\is_string($resource) || $operation instanceof CollectionOperationInterface) ? '_c' : '_i');
        if ($operation && isset($this->localOperationCache[$localOperationCacheKey])) {
            return $this->generateSymfonyRoute($resource, $referenceType, $this->localOperationCache[$localOperationCacheKey], $context, $this->localIdentifiersExtractorOperationCache[$localOperationCacheKey] ?? null);
        }

        if (!$this->resourceClassResolver->isResourceClass($resourceClass)) {
            return $this->generateSkolemIri($resource, $referenceType, $operation, $context, $resourceClass);
        }

        // This is only for when a class (that is not a resource) extends another one that is a resource, we should remove this behavior
        if (!\is_string($resource) && !isset($context['force_resource_class'])) {
            $resourceClass = $this->getResourceClass($resource, true);
        }

        if (!$operation) {
            $operation = (new Get())->withClass($resourceClass);
        }

        if ($operation instanceof HttpOperation && 301 === $operation->getStatus()) {
            $operation = ($operation instanceof CollectionOperationInterface ? new GetCollection() : new Get())->withClass($operation->getClass());
            unset($context['uri_variables']);
        }

        $identifiersExtractorOperation = $operation;
        // In symfony the operation name is the route name, try to find one if none provided
        if (
            !$operation->getName()
            || ($operation instanceof HttpOperation && 'POST' === $operation->getMethod())
        ) {
            $forceCollection = $operation instanceof CollectionOperationInterface;
            try {
                $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation(null, $forceCollection, true);
                $identifiersExtractorOperation = $operation;
            } catch (OperationNotFoundException) {
            }
        }

        if (!$operation->getName() || ($operation instanceof HttpOperation && $operation->getUriTemplate() && str_starts_with($operation->getUriTemplate(), SkolemIriConverter::$skolemUriTemplate))) {
            return $this->generateSkolemIri($resource, $referenceType, $operation, $context, $resourceClass);
        }

        $this->localOperationCache[$localOperationCacheKey] = $operation;
        $this->localIdentifiersExtractorOperationCache[$localOperationCacheKey] = $identifiersExtractorOperation;

        return $this->generateSymfonyRoute($resource, $referenceType, $operation, $context, $identifiersExtractorOperation);
    }

    private function generateSkolemIri(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = [], ?string $resourceClass = null): string
    {
        if (!$this->decorated) {
            throw new InvalidArgumentException(\sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass));
        }

        // Use a skolem iri, the route is defined in genid.xml
        return $this->decorated->getIriFromResource($resource, $referenceType, $operation, $context);
    }

    private function generateSymfonyRoute(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = [], ?Operation $identifiersExtractorOperation = null): string
    {
        $identifiers = $context['uri_variables'] ?? [];

        if (\is_object($resource)) {
            try {
                $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($resource, $identifiersExtractorOperation, $context);
            } catch (InvalidArgumentException|RuntimeException $e) {
                // We can try using context uri variables if any
                if (!$identifiers) {
                    throw new InvalidArgumentException(\sprintf('Unable to generate an IRI for the item of type "%s"', $operation->getClass()), $e->getCode(), $e);
                }
            }
        }

        try {
            $routeName = $operation instanceof HttpOperation ? ($operation->getRouteName() ?? $operation->getName()) : $operation->getName();

            return $this->router->generate($routeName, $identifiers, $operation->getUrlGenerationStrategy() ?? $referenceType);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(\sprintf('Unable to generate an IRI for the item of type "%s"', $operation->getClass()), $e->getCode(), $e);
        }
    }
}
