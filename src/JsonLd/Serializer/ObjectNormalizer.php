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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Serializer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface as BaseCacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Decorates the output with JSON-LD metadata when appropriate, but otherwise just
 * passes through to the decorated normalizer.
 */
final class ObjectNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use JsonLdContextTrait;

    public const FORMAT = 'jsonld';

    public function __construct(private readonly NormalizerInterface $decorated, private readonly IriConverterInterface|LegacyIriConverterInterface $iriConverter, private AnonymousContextBuilderInterface $anonymousContextBuilder)
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

        /*
         * Converts the normalized data array of a resource into an IRI, if the
         * normalized data array is empty.
         *
         * This is useful when traversing from a non-resource towards an attribute
         * which is a resource, as we do not have the benefit of {@see ApiProperty::isReadableLink}.
         *
         * It must not be propagated to resources, as {@see ApiProperty::isReadableLink}
         * should take effect.
         */
        $context['api_empty_resource_as_iri'] = true;

        $data = $this->decorated->normalize($object, $format, $context);
        if (!\is_array($data) || !$data) {
            return $data;
        }

        if (isset($originalResource)) {
            try {
                $context['output']['iri'] = $this->iriConverter->getIriFromResource($originalResource);
            } catch (InvalidArgumentException) {
                // The original resource has no identifiers
            }
            $context['api_resource'] = $originalResource;
        }

        $metadata = $this->createJsonLdContext($this->anonymousContextBuilder, $object, $context);

        return $metadata + $data;
    }
}
