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

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class LegacyOpenApiNormalizer implements NormalizerInterface
{
    public const SPEC_VERSION = 'spec_version';
    private array $defaultContext = [
        self::SPEC_VERSION => '3.1.0',
    ];

    public function __construct(private readonly NormalizerInterface $decorated, array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $openapi = $this->decorated->normalize($object, $format, $context);

        if ('3.0.0' !== ($context['spec_version'] ?? null)) {
            return $openapi;
        }

        $schemas = &$openapi['components']['schemas'];
        $openapi['openapi'] = '3.0.0';
        foreach ($openapi['components']['schemas'] as $name => $component) {
            foreach ($component['properties'] ?? [] as $property => $value) {
                if (\is_array($value['type'] ?? false)) {
                    foreach ($value['type'] as $type) {
                        $schemas[$name]['properties'][$property]['anyOf'][] = ['type' => $type];
                    }
                    unset($schemas[$name]['properties'][$property]['type']);
                }

                if (\is_array($value['examples'] ?? false)) {
                    $schemas[$name]['properties'][$property]['example'] = $value['examples'];
                    unset($schemas[$name]['properties'][$property]['examples']);
                }
            }
        }

        return $openapi;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    /**
     * @param string|null $format
     */
    public function getSupportedTypes($format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }
}
