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

use ApiPlatform\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface as LegacyResourceClassResolverInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface as BaseCacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Decorates the output with JSON API metadata when appropriate, but otherwise
 * just passes through to the decorated normalizer.
 */
final class ObjectNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use ClassInfoTrait;

    public const FORMAT = 'jsonapi';

    public function __construct(private readonly NormalizerInterface $decorated, private readonly IriConverterInterface|LegacyIriConverterInterface $iriConverter, private readonly ResourceClassResolverInterface|LegacyResourceClassResolverInterface $resourceClassResolver, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes($format): array
    {
        // @deprecated remove condition when support for symfony versions under 6.3 is dropped
        if (!method_exists($this->decorated, 'getSupportedTypes')) {
            return [
                '*' => $this->decorated instanceof BaseCacheableSupportsMethodInterface && $this->decorated->hasCacheableSupportsMethod(),
            ];
        }

        return self::FORMAT === $format ? $this->decorated->getSupportedTypes($format) : [];
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

        return $this->decorated instanceof BaseCacheableSupportsMethodInterface && $this->decorated->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (isset($context['api_resource'])) {
            $originalResource = $context['api_resource'];
            unset($context['api_resource']);
        }

        $data = $this->decorated->normalize($object, $format, $context);
        if (!\is_array($data) || isset($context['api_attribute'])) {
            return $data;
        }

        if (isset($originalResource)) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($originalResource);
            $resourceData = [
                'id' => $this->iriConverter->getIriFromResource($originalResource),
                'type' => $this->resourceMetadataFactory->create($resourceClass)->getOperation()->getShortName(),
            ];
        } else {
            $resourceData = [
                'id' => $this->iriConverter->getIriFromResource($object),
                'type' => (new \ReflectionClass($this->getObjectClass($object)))->getShortName(),
            ];
        }

        if ($data) {
            $resourceData['attributes'] = $data;
        }

        return ['data' => $resourceData];
    }
}
