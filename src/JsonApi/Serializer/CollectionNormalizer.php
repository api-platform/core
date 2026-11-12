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

namespace ApiPlatform\JsonApi\Serializer;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\IriHelper;
use ApiPlatform\Serializer\AbstractCollectionNormalizer;
use ApiPlatform\State\Util\RequestParser;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizes collections in the JSON API format.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Hamza Amrouche <hamza@les-tilleuls.coop>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class CollectionNormalizer extends AbstractCollectionNormalizer
{
    public const FORMAT = 'jsonapi';

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, string $pageParameterName, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory)
    {
        parent::__construct($resourceClassResolver, $pageParameterName, $resourceMetadataFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaginationData(iterable $object, array $context = []): array
    {
        [$paginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems] = $this->getPaginationConfig($object, $context);
        $parsed = $this->parseUri($context['uri'] ?? '/');

        $operation = $context['operation'] ?? $this->getOperation($context);
        $urlGenerationStrategy = $operation->getUrlGenerationStrategy();

        $data = [
            'links' => [
                'self' => $this->createPageIri($parsed, $paginated ? $currentPage : null, $urlGenerationStrategy),
            ],
        ];

        if ($paginated) {
            if (null !== $lastPage) {
                $data['links']['first'] = $this->createPageIri($parsed, 1., $urlGenerationStrategy);
                $data['links']['last'] = $this->createPageIri($parsed, $lastPage, $urlGenerationStrategy);
            }

            if (1. !== $currentPage) {
                $data['links']['prev'] = $this->createPageIri($parsed, $currentPage - 1., $urlGenerationStrategy);
            }

            if (null !== $lastPage && $currentPage !== $lastPage || null === $lastPage && $pageTotalItems >= $itemsPerPage) {
                $data['links']['next'] = $this->createPageIri($parsed, $currentPage + 1., $urlGenerationStrategy);
            }
        }

        if (null !== $totalItems) {
            $data['meta']['totalItems'] = $totalItems;
        }

        if ($paginator) {
            $data['meta']['itemsPerPage'] = (int) $itemsPerPage;
            $data['meta']['currentPage'] = (int) $currentPage;
        }

        return $data;
    }

    /**
     * @return array{parts: array<string, mixed>, parameters: array<string, mixed>}
     */
    private function parseUri(string $uri): array
    {
        $parts = parse_url($uri);
        if (false === $parts) {
            throw new InvalidArgumentException(\sprintf('The request URI "%s" is malformed.', $uri));
        }

        return ['parts' => $parts, 'parameters' => isset($parts['query']) ? RequestParser::parseRequestParams($parts['query']) : []];
    }

    /**
     * @param array{parts: array<string, mixed>, parameters: array<string, mixed>} $parsed
     */
    private function createPageIri(array $parsed, ?float $page, ?int $urlGenerationStrategy): string
    {
        $parameters = $parsed['parameters'];
        $pageParameters = \is_array($parameters['page'] ?? null) ? $parameters['page'] : [];
        unset($parameters['page'], $parameters[$this->pageParameterName], $pageParameters[$this->pageParameterName]);

        if (null !== $page) {
            $pageParameters = [$this->pageParameterName => $page] + $pageParameters;
        }

        if ([] !== $pageParameters) {
            $parameters['page'] = $pageParameters;
        }

        return IriHelper::createIri($parsed['parts'], $parameters, null, null, $urlGenerationStrategy);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    protected function getItemsData(iterable $object, ?string $format = null, array $context = []): array
    {
        $data = [
            'data' => [],
        ];

        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context);
            if (!\is_array($item)) {
                throw new UnexpectedValueException('Expected item to be an array');
            }

            if (!isset($item['data'])) {
                throw new UnexpectedValueException('The JSON API document must contain a "data" key.');
            }

            $data['data'][] = $item['data'];

            if (isset($item['included'])) {
                $data['included'] = array_values(array_unique(array_merge($data['included'] ?? [], $item['included']), \SORT_REGULAR));
            }
        }

        return $data;
    }
}
