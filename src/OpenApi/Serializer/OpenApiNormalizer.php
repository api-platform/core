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

namespace ApiPlatform\OpenApi\Serializer;

use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Generates an OpenAPI v3 specification.
 */
final class OpenApiNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'json';
    public const JSON_FORMAT = 'jsonopenapi';
    public const YAML_FORMAT = 'yamlopenapi';
    private const EXTENSION_PROPERTIES_KEY = 'extensionProperties';

    public function __construct(private readonly NormalizerInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $pathsCallback = static fn ($decoratedObject): array => $decoratedObject instanceof Paths ? $decoratedObject->getPaths() : [];
        $context[AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS] = true;
        $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] = true;
        $context[AbstractNormalizer::CALLBACKS] = [
            'paths' => $pathsCallback,
        ];

        return $this->recursiveClean($this->decorated->normalize($object, $format, $context));
    }

    private function recursiveClean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (self::EXTENSION_PROPERTIES_KEY === $key) {
                foreach ($data[self::EXTENSION_PROPERTIES_KEY] as $extensionPropertyKey => $extensionPropertyValue) {
                    $data[$extensionPropertyKey] = $extensionPropertyValue;
                }
                continue;
            }

            if (\is_array($value)) {
                $data[$key] = $this->recursiveClean($value);
            }
        }

        unset($data[self::EXTENSION_PROPERTIES_KEY]);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return (self::FORMAT === $format || self::JSON_FORMAT === $format || self::YAML_FORMAT === $format) && $data instanceof OpenApi;
    }

    public function getSupportedTypes($format): array
    {
        return (self::FORMAT === $format || self::JSON_FORMAT === $format || self::YAML_FORMAT === $format) ? [OpenApi::class => true] : [];
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

        return true;
    }
}
