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

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\CacheableSupportsMethodInterface;
use ApiPlatform\State\Pagination\CursorPaginatorInterface;
use ApiPlatform\Util\IriHelper;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface as BaseCacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Adds a view key to the result of a paginated Hydra collection, if the
 * collection is a CursorPaginatorInterface.
 *
 * @author Priyadi Iman Nurcahyo <priyadi@rekalogika.com>
 */
final class CursorBasedPartialCollectionViewNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    public function __construct(private readonly NormalizerInterface $collectionNormalizer, private readonly string $pageParameterName = 'page', private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null, private readonly int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);

        if (!$object instanceof CursorPaginatorInterface || isset($context['api_sub_level'])) {
            return $data;
        }

        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }

        // (same TODO message retained from PartialCollectionViewNormalizer)
        // TODO: This needs to be changed as well as I wrote in the CollectionFiltersNormalizer
        // We should not rely on the request_uri but instead rely on the UriTemplate
        // This needs that we implement the RFC and that we do more parsing before calling the serialization (MainController)
        $parsed = IriHelper::parseIri($context['uri'] ?? $context['request_uri'] ?? '/', $this->pageParameterName);

        $operation = $context['operation'] ?? null;
        if (!$operation && $this->resourceMetadataFactory && isset($context['resource_class'])) {
            $operation = $this->resourceMetadataFactory->create($context['resource_class'])->getOperation($context['operation_name'] ?? null);
        }

        $data['hydra:view'] = ['@type' => 'hydra:PartialCollectionView'];

        $data['hydra:view']['@id'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $object->getCurrentPageCursor(), $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy);

        if (($firstPageCursor = $object->getFirstPageCursor()) !== null) {
            $data['hydra:view']['hydra:first'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $firstPageCursor, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy);
        }

        if (($lastPageCursor = $object->getLastPageCursor()) !== null) {
            $data['hydra:view']['hydra:last'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPageCursor, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy);
        }

        if (($nextPageCursor = $object->getNextPageCursor()) !== null) {
            $data['hydra:view']['hydra:next'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $nextPageCursor, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy);
        }

        if (($previousPageCursor = $object->getPreviousPageCursor()) !== null) {
            $data['hydra:view']['hydra:previous'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $previousPageCursor, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes($format): array
    {
        // @deprecated remove condition when support for symfony versions under 6.3 is dropped
        if (!method_exists($this->collectionNormalizer, 'getSupportedTypes')) {
            return [
                '*' => $this->collectionNormalizer instanceof CacheableSupportsMethodInterface && $this->collectionNormalizer->hasCacheableSupportsMethod(),
            ];
        }

        return $this->collectionNormalizer->getSupportedTypes($format);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        if (method_exists(Serializer::class, 'getSupportedTypes')) {
            trigger_deprecation(
                'api-platform/core',
                '3.1',
                'The "%s()" method is deprecated, use "getSupportedTypes()" instead.',
                __METHOD__
            );
        }

        return $this->collectionNormalizer instanceof BaseCacheableSupportsMethodInterface && $this->collectionNormalizer->hasCacheableSupportsMethod();
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
}
