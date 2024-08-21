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

namespace ApiPlatform\Laravel\Routing;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
// use Illuminate\Routing\Router;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

class IriConverter implements IriConverterInterface
{
    use ClassInfoTrait;
    use ResourceClassInfoTrait;
    // use UriVariablesResolverTrait;

    /**
     * @var array<string, Operation>
     */
    private array $localOperationCache = [];

    /**
     * @var array<string, Operation>
     */
    private array $localIdentifiersExtractorOperationCache = [];

    // , UriVariablesConverterInterface $uriVariablesConverter = null TODO
    /**
     * @param ProviderInterface<object> $provider
     */
    public function __construct(private readonly ProviderInterface $provider, private readonly OperationMetadataFactoryInterface $operationMetadataFactory, private readonly RouterInterface $router, private readonly IdentifiersExtractorInterface $identifiersExtractor, ResourceClassResolverInterface $resourceClassResolver, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ?IriConverterInterface $decorated = null)
    {
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object
    {
        $parameters = $this->router->match($iri);
        if (!isset($parameters['_api_resource_class'], $parameters['_api_operation_name'], $parameters['uri_variables'])) {
            throw new InvalidArgumentException(\sprintf('No resource associated to "%s".', $iri));
        }

        $operation = $this->resourceMetadataCollectionFactory->create($parameters['_api_resource_class'])->getOperation($parameters['_api_operation_name']);

        if ($operation instanceof CollectionOperationInterface) {
            throw new InvalidArgumentException(\sprintf('The iri "%s" references a collection not an item.', $iri));
        }

        if (!$operation instanceof HttpOperation) {
            throw new RuntimeException(\sprintf('The iri "%s" does not reference an HTTP operation.', $iri));
        }

        if ($item = $this->provider->provide($operation, $parameters['uri_variables'], $context)) {
            return $item; // @phpstan-ignore-line
        }

        throw new ItemNotFoundException(\sprintf('Item not found for "%s".', $iri));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function getIriFromResource(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = []): ?string
    {
        $resourceClass = $context['force_resource_class'] ?? (\is_string($resource) ? $resource : $this->getObjectClass($resource));
        if ($resource instanceof Relation) {
            $resourceClass = $this->getObjectClass($resource->getRelated());
        }

        if (isset($context['item_uri_template'])) {
            $operation = $this->operationMetadataFactory->create($context['item_uri_template']);
        }

        $localOperationCacheKey = ($operation?->getName() ?? '').$resourceClass.(\is_string($resource) ? '_c' : '_i');
        if ($operation && isset($this->localOperationCache[$localOperationCacheKey])) {
            return $this->generateRoute($resource, $referenceType, $this->localOperationCache[$localOperationCacheKey], $context, $this->localIdentifiersExtractorOperationCache[$localOperationCacheKey] ?? null);
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

        if (!$operation->getName() || ($operation instanceof HttpOperation && $operation->getUriTemplate() && str_starts_with($operation->getUriTemplate(), SkolemIriConverter::SKOLEM_URI_TEMPLATE))) {
            return $this->generateSkolemIri($resource, $referenceType, $operation, $context, $resourceClass);
        }

        $this->localOperationCache[$localOperationCacheKey] = $operation;
        $this->localIdentifiersExtractorOperationCache[$localOperationCacheKey] = $identifiersExtractorOperation;

        return $this->generateRoute($resource, $referenceType, $operation, $context, $identifiersExtractorOperation);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function generateRoute(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = [], ?Operation $identifiersExtractorOperation = null): string
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
            return $this->router->generate($operation->getName(), $identifiers, $operation->getUrlGenerationStrategy() ?? $referenceType);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(\sprintf('Unable to generate an IRI for the item of type "%s"', $operation->getClass()), $e->getCode(), $e);
        }
    }

    /**
     * @param object|class-string  $resource
     * @param array<string, mixed> $context
     */
    private function generateSkolemIri(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = [], ?string $resourceClass = null): string
    {
        if (!$this->decorated) {
            throw new InvalidArgumentException(\sprintf('Unable to generate an IRI for the item of type "%s"', $resourceClass));
        }

        // Use a skolem iri, the route is defined in genid.xml
        return $this->decorated->getIriFromResource($resource, $referenceType, $operation, $context);
    }
}
