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

    public function __construct(private readonly NormalizerInterface $decorated, $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $openapi = $this->decorated->normalize($object, $format, $context);

        if ('3.0' !== $context['spec_version']) {
            return $openapi;
        }

        foreach ($openapi['components']['schemas'] as &$schema) {
            foreach ($schema['properties'] ?? [] as &$property) {
                if (isset($property['type']) && \is_array($property['type'])) {
                    $property['type'] = $property['type'][0];
                }

                if (isset($property['type']['owl:maxCardinality'])) {
                    unset($proprety['type']['owl:maxCardinality']);
                }
            }
        }

        return $openapi;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes($format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }
}
