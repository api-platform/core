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

use ApiPlatform\Hydra\IriTemplateMapping;
use ApiPlatform\Hydra\State\Util\SearchHelperTrait;
use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Util\StateOptionsTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Enhances the result of collection by adding the filters applied on collection.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionFiltersNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use HydraPrefixTrait;
    use SearchHelperTrait;
    use StateOptionsTrait;
    private ?ContainerInterface $filterLocator = null;

    /**
     * @param ContainerInterface   $filterLocator  The new filter locator or the deprecated filter collection
     * @param array<string, mixed> $defaultContext
     */
    public function __construct(
        private readonly NormalizerInterface $collectionNormalizer,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        ?ContainerInterface $filterLocator = null,
        private readonly array $defaultContext = [],
    ) {
        $this->filterLocator = $filterLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format, $context);
    }

    /**
     * @param string|null $format
     */
    public function getSupportedTypes($format): array
    {
        return $this->collectionNormalizer->getSupportedTypes($format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
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

        $parameters = $operation->getParameters();
        $resourceFilters = $operation->getFilters();
        if (!$resourceFilters && !$parameters) {
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

        $resourceClass = $this->getStateOptionsClass($operation, $resourceClass);

        if ($currentFilters || ($parameters && \count($parameters))) {
            $hydraPrefix = $this->getHydraPrefix($context + $this->defaultContext);
            ['mapping' => $mapping, 'keys' => $keys] = $this->getSearchMappingAndKeys($operation, $resourceClass, $currentFilters, $parameters, [$this, 'getFilter']);
            $data[$hydraPrefix.'search'] = [
                '@type' => $hydraPrefix.'IriTemplate',
                $hydraPrefix.'template' => \sprintf('%s{?%s}', $requestParts['path'], implode(',', $keys)),
                $hydraPrefix.'variableRepresentation' => 'BasicRepresentation',
                $hydraPrefix.'mapping' => $this->convertMappingToArray($mapping),
            ];
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
     * @param list<IriTemplateMapping> $mapping
     *
     * @return array<array<string, mixed>>
     */
    private function convertMappingToArray(array $mapping): array
    {
        $convertedMapping = [];
        foreach ($mapping as $m) {
            $converted = [
                '@type' => 'IriTemplateMapping',
                'variable' => $m->variable,
                'property' => $m->property,
            ];

            if (null !== ($r = $m->required)) {
                $converted['required'] = $r;
            }

            $convertedMapping[] = $converted;
        }

        return $convertedMapping;
    }

    /**
     * Gets a filter with a backward compatibility.
     */
    private function getFilter(string $filterId): ?FilterInterface
    {
        if ($this->filterLocator && $this->filterLocator->has($filterId)) {
            return $this->filterLocator->get($filterId);
        }

        return null;
    }
}
