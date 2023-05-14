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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Decorates the output with JSON API metadata when appropriate, but otherwise
 * just passes through to the decorated normalizer.
 */
final class ObjectNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use ClassInfoTrait;

    public const FORMAT = 'jsonapi';

    public function __construct(private readonly NormalizerInterface $decorated, private readonly IriConverterInterface $iriConverter, private readonly ResourceClassResolverInterface $resourceClassResolver, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->decorated instanceof CacheableSupportsMethodInterface && $this->decorated->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
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
