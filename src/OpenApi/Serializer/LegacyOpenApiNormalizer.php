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

    private const SCHEMA_BRANCH_KEYS = ['properties', 'patternProperties'];
    private const SCHEMA_LIST_KEYS = ['allOf', 'oneOf', 'anyOf'];
    private const SCHEMA_NESTED_KEYS = ['items', 'additionalProperties', 'not', 'contains', 'propertyNames', 'if', 'then', 'else'];

    private array $defaultContext = [
        self::SPEC_VERSION => '3.2.0',
    ];

    public function __construct(private readonly NormalizerInterface $decorated, array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $openapi = $this->decorated->normalize($data, $format, $context);

        if ('3.0.0' !== ($context['spec_version'] ?? null)) {
            return $openapi;
        }

        $openapi['openapi'] = '3.0.0';

        foreach ($openapi['components']['schemas'] ?? [] as $name => $component) {
            $openapi['components']['schemas'][$name] = $this->downgradeSchema($component);
        }

        foreach ($openapi['paths'] ?? [] as $path => $operations) {
            $openapi['paths'][$path] = $this->downgradePathItem($operations);
        }

        return $openapi;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    private function downgradePathItem(mixed $pathItem): mixed
    {
        if (!\is_array($pathItem)) {
            return $pathItem;
        }

        foreach ($pathItem as $method => $operation) {
            if (!\is_array($operation)) {
                continue;
            }

            if (isset($operation['requestBody']['content']) && \is_array($operation['requestBody']['content'])) {
                foreach ($operation['requestBody']['content'] as $mediaType => $media) {
                    if (isset($media['schema'])) {
                        $pathItem[$method]['requestBody']['content'][$mediaType]['schema'] = $this->downgradeSchema($media['schema']);
                    }
                }
            }

            foreach ($operation['responses'] ?? [] as $status => $response) {
                if (!\is_array($response)) {
                    continue;
                }
                foreach ($response['content'] ?? [] as $mediaType => $media) {
                    if (isset($media['schema'])) {
                        $pathItem[$method]['responses'][$status]['content'][$mediaType]['schema'] = $this->downgradeSchema($media['schema']);
                    }
                }
            }

            foreach ($operation['parameters'] ?? [] as $index => $parameter) {
                if (isset($parameter['schema'])) {
                    $pathItem[$method]['parameters'][$index]['schema'] = $this->downgradeSchema($parameter['schema']);
                }
            }
        }

        return $pathItem;
    }

    private function downgradeSchema(mixed $schema): mixed
    {
        if (!\is_array($schema)) {
            return $schema;
        }

        if (\is_array($schema['type'] ?? null)) {
            $types = array_values($schema['type']);
            $nullable = \in_array('null', $types, true);
            $nonNull = array_values(array_filter($types, static fn ($t) => 'null' !== $t));

            if (1 === \count($nonNull)) {
                $schema['type'] = $nonNull[0];
            } elseif ([] === $nonNull) {
                unset($schema['type']);
            } else {
                unset($schema['type']);
                $schema['anyOf'] = array_map(static fn ($t) => ['type' => $t], $nonNull);
            }

            if ($nullable) {
                $schema['nullable'] = true;
            }
        }

        if (\array_key_exists('examples', $schema)) {
            $schema['example'] = $schema['examples'];
            unset($schema['examples']);
        }

        foreach (self::SCHEMA_BRANCH_KEYS as $key) {
            if (!isset($schema[$key]) || !\is_array($schema[$key])) {
                continue;
            }
            foreach ($schema[$key] as $name => $child) {
                $schema[$key][$name] = $this->downgradeSchema($child);
            }
        }

        foreach (self::SCHEMA_LIST_KEYS as $key) {
            if (!isset($schema[$key]) || !\is_array($schema[$key])) {
                continue;
            }
            foreach ($schema[$key] as $index => $child) {
                $schema[$key][$index] = $this->downgradeSchema($child);
            }
        }

        foreach (self::SCHEMA_NESTED_KEYS as $key) {
            if (!isset($schema[$key])) {
                continue;
            }
            if (\is_array($schema[$key])) {
                $schema[$key] = $this->downgradeSchema($schema[$key]);
            }
        }

        return $schema;
    }
}
