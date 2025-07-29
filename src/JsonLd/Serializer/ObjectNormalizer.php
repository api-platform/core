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

use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IriConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Decorates the output with JSON-LD metadata when appropriate, but otherwise just
 * passes through to the decorated normalizer.
 */
final class ObjectNormalizer implements NormalizerInterface
{
    use JsonLdContextTrait;

    public const FORMAT = 'jsonld';

    public function __construct(private readonly NormalizerInterface $decorated, private readonly IriConverterInterface $iriConverter, private AnonymousContextBuilderInterface $anonymousContextBuilder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $this->decorated->supportsNormalization($data, $format, $context);
    }

    /**
     * @param string|null $format
     */
    public function getSupportedTypes($format): array
    {
        return self::FORMAT === $format ? $this->decorated->getSupportedTypes($format) : [];
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
