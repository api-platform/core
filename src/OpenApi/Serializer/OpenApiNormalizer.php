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

/**
 * Generates an OpenAPI v3 specification.
 */
final class OpenApiNormalizer implements NormalizerInterface
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
        $pathsCallback = $this->getPathsCallBack();
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

    /**
     * @param string|null $format
     */
    public function getSupportedTypes($format): array
    {
        return (self::FORMAT === $format || self::JSON_FORMAT === $format || self::YAML_FORMAT === $format) ? [OpenApi::class => true] : [];
    }

    private function getPathsCallBack(): \Closure
    {
        return static function ($decoratedObject): array {
            if ($decoratedObject instanceof Paths) {
                $paths = $decoratedObject->getPaths();

                // sort paths by tags, then by path for each tag
                uksort($paths, function ($keyA, $keyB) use ($paths) {
                    $a = $paths[$keyA];
                    $b = $paths[$keyB];

                    $tagsA = [
                        ...($a->getGet()?->getTags() ?? []),
                        ...($a->getPost()?->getTags() ?? []),
                        ...($a->getPatch()?->getTags() ?? []),
                        ...($a->getPut()?->getTags() ?? []),
                        ...($a->getDelete()?->getTags() ?? []),
                    ];
                    sort($tagsA);

                    $tagsB = [
                        ...($b->getGet()?->getTags() ?? []),
                        ...($b->getPost()?->getTags() ?? []),
                        ...($b->getPatch()?->getTags() ?? []),
                        ...($b->getPut()?->getTags() ?? []),
                        ...($b->getDelete()?->getTags() ?? []),
                    ];
                    sort($tagsB);

                    return match (true) {
                        current($tagsA) === current($tagsB) => $keyA <=> $keyB,
                        default => current($tagsA) <=> current($tagsB),
                    };
                });

                return $paths;
            }

            return [];
        };
    }
}
