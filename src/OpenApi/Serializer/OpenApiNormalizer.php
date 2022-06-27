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
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Generates an OpenAPI v3 specification.
 */
final class OpenApiNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'json';
    private const EXTENSION_PROPERTIES_KEY = 'extensionProperties';

    private $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $pathsCallback = static function ($innerObject) {
            return $innerObject instanceof Paths ? $innerObject->getPaths() : [];
        };
        $context[AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS] = true;
        $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] = true;
        $context[AbstractObjectNormalizer::CALLBACKS] = [
            'paths' => $pathsCallback,
        ];

        return $this->recursiveClean($this->decorated->normalize($object, $format, $context));
    }

    private function recursiveClean($data): array
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
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $data instanceof OpenApi;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

class_alias(OpenApiNormalizer::class, \ApiPlatform\Core\OpenApi\Serializer\OpenApiNormalizer::class);
