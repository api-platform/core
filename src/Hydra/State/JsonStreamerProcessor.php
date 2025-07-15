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

namespace ApiPlatform\Hydra\State;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Hydra\IriTemplate;
use ApiPlatform\Hydra\IriTemplateMapping;
use ApiPlatform\Hydra\PartialCollectionView;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\QueryParameterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\IriHelper;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Util\HttpResponseHeadersTrait;
use ApiPlatform\State\Util\HttpResponseStatusTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\JsonStreamer\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements ProcessorInterface<mixed,mixed>
 */
final class JsonStreamerProcessor implements ProcessorInterface
{
    use HttpResponseHeadersTrait;
    use HttpResponseStatusTrait;

    /**
     * @param ProcessorInterface<mixed,mixed>            $processor
     * @param StreamWriterInterface<array<string,mixed>> $jsonStreamer
     */
    public function __construct(
        private readonly ProcessorInterface $processor,
        private readonly StreamWriterInterface $jsonStreamer,
        ?IriConverterInterface $iriConverter = null,
        ?ResourceClassResolverInterface $resourceClassResolver = null,
        ?OperationMetadataFactoryInterface $operationMetadataFactory = null,
        private readonly string $pageParameterName = 'page',
        private readonly string $enabledParameterName = 'pagination',
        private readonly int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH,
    ) {
        $this->resourceClassResolver = $resourceClassResolver;
        $this->iriConverter = $iriConverter;
        $this->operationMetadataFactory = $operationMetadataFactory;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$operation->getJsonStream() || !($request = $context['request'] ?? null)) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        // TODO: remove this before merging
        if ($request->query->has('skip_json_stream')) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        if ($operation instanceof Error || $data instanceof Response) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        if ($operation instanceof CollectionOperationInterface) {
            $requestUri = $request->getRequestUri() ?? '';
            $collection = new Collection();
            $collection->member = $data;
            $collection->view = $this->getView($data, $requestUri, $operation);

            if ($operation->getParameters()) {
                $collection->search = $this->getSearch($operation, $requestUri);
            }

            if ($data instanceof PaginatorInterface) {
                $collection->totalItems = $data->getTotalItems();
            }

            if (\is_array($data) || ($data instanceof \Countable && !$data instanceof PartialPaginatorInterface)) {
                $collection->totalItems = \count($data);
            }

            $data = $this->jsonStreamer->write(
                $collection,
                Type::generic(Type::object($collection::class), Type::object($operation->getClass())),
                ['data' => $data, 'operation' => $operation],
            );
        } else {
            $data = $this->jsonStreamer->write($data, Type::object($operation->getClass()), [
                'data' => $data,
                'operation' => $operation,
            ]);
        }

        /** @var iterable<string> $data */
        $response = new StreamedResponse(
            $data,
            $this->getStatus($request, $operation, $context),
            $this->getHeaders($request, $operation, $context)
        );

        return $this->processor->process($response, $operation, $uriVariables, $context);
    }

    // TODO: These come from our Hydra collection normalizer, try to share the logic
    private function getSearch(Operation $operation, string $requestUri): IriTemplate
    {
        /** @var list<IriTemplateMapping> */
        $mapping = [];
        $keys = [];

        foreach ($operation->getParameters() ?? [] as $key => $parameter) {
            if (!$parameter instanceof QueryParameterInterface || false === $parameter->getHydra()) {
                continue;
            }

            if (!($property = $parameter->getProperty())) {
                continue;
            }

            $keys[] = $key;
            $m = new IriTemplateMapping(
                variable: $key,
                property: $property,
                required: $parameter->getRequired() ?? false
            );
            $mapping[] = $m;
        }

        $parts = parse_url($requestUri);

        return new IriTemplate(
            variableRepresentation: 'BasicRepresentation',
            mapping: $mapping,
            template: \sprintf('%s{?%s}', $parts['path'] ?? '', implode(',', $keys)),
        );
    }

    private function getView(mixed $object, string $requestUri, Operation $operation): PartialCollectionView
    {
        $currentPage = $lastPage = $itemsPerPage = $pageTotalItems = null;
        if ($paginated = ($object instanceof PartialPaginatorInterface)) {
            if ($object instanceof PaginatorInterface) {
                $paginated = 1. !== $lastPage = $object->getLastPage();
            } else {
                $itemsPerPage = $object->getItemsPerPage();
                $pageTotalItems = (float) \count($object);
            }

            $currentPage = $object->getCurrentPage();
        }

        // TODO: This needs to be changed as well as I wrote in the CollectionFiltersNormalizer
        // We should not rely on the request_uri but instead rely on the UriTemplate
        // This needs that we implement the RFC and that we do more parsing before calling the serialization (MainController)
        $parsed = IriHelper::parseIri($requestUri, $this->pageParameterName);
        $appliedFilters = $parsed['parameters'];
        unset($appliedFilters[$this->enabledParameterName]);

        $urlGenerationStrategy = $operation->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy;
        $id = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null, $urlGenerationStrategy);
        if (!$appliedFilters && !$paginated) {
            return new PartialCollectionView($id);
        }

        $first = $last = $previous = $next = null;
        if (null !== $lastPage) {
            $first = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1., $urlGenerationStrategy);
            $last = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage, $urlGenerationStrategy);
        }

        if (1. !== $currentPage) {
            $previous = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1., $urlGenerationStrategy);
        }

        if ((null !== $lastPage && $currentPage < $lastPage) || (null === $lastPage && $pageTotalItems >= $itemsPerPage)) {
            $next = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1., $urlGenerationStrategy);
        }

        return new PartialCollectionView($id, $first, $last, $previous, $next);
    }
}
