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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\Api\FilterInterface;
use ApiPlatform\Api\FilterLocatorTrait;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Enhances the result of collection by adding the filters applied on collection.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionFiltersNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    use FilterLocatorTrait;

    /**
     * @param ContainerInterface $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(private readonly NormalizerInterface $collectionNormalizer, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ResourceClassResolverInterface $resourceClassResolver, ContainerInterface $filterLocator)
    {
        $this->setFilterLocator($filterLocator);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format, $context);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->collectionNormalizer instanceof CacheableSupportsMethodInterface && $this->collectionNormalizer->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (($context[AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS] ?? false) && $object instanceof \ArrayObject && !\count($object)) {
            return $object;
        }

        $data = $this->collectionNormalizer->normalize($object, $format, $context);
        if (!isset($context['resource_class']) || isset($context['api_sub_level'])) {
            return $data;
        }

        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($context['operation_name'] ?? null);
        $resourceFilters = $operation->getFilters();
        if (!$resourceFilters) {
            return $data;
        }
        $requestParts = parse_url($context['request_uri'] ?? '');
        if (!\is_array($requestParts)) {
            return $data;
        }
        $currentFilters = [];
        foreach ($resourceFilters as $filterId) {
            if ($filter = $this->getFilter($filterId)) {
                $currentFilters[] = $filter;
            }
        }

        if (($options = $operation?->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
            $resourceClass = $options->getEntityClass();
        }

        if ($currentFilters) {
            $data['hydra:search'] = $this->getSearch($resourceClass, $requestParts, $currentFilters);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        if ($this->collectionNormalizer instanceof NormalizerAwareInterface) {
            $this->collectionNormalizer->setNormalizer($normalizer);
        }
    }

    /**
     * Returns the content of the Hydra search property.
     *
     * @param FilterInterface[] $filters
     */
    private function getSearch(string $resourceClass, array $parts, array $filters): array
    {
        $variables = [];
        $mapping = [];
        foreach ($filters as $filter) {
            foreach ($filter->getDescription($resourceClass) as $variable => $data) {
                $variables[] = $variable;
                $mapping[] = ['@type' => 'IriTemplateMapping', 'variable' => $variable, 'property' => $data['property'], 'required' => $data['required']];
            }
        }

        return ['@type' => 'hydra:IriTemplate', 'hydra:template' => sprintf('%s{?%s}', $parts['path'], implode(',', $variables)), 'hydra:variableRepresentation' => 'BasicRepresentation', 'hydra:mapping' => $mapping];
    }
}
