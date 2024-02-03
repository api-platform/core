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

use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface as BaseCacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Removes features unsupported by Amazon API Gateway.
 *
 * @see https://docs.aws.amazon.com/apigateway/latest/developerguide/api-gateway-known-issues.html
 *
 * @internal
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ApiGatewayNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const API_GATEWAY = 'api_gateway';
    private array $defaultContext = [
        self::API_GATEWAY => false,
    ];

    public function __construct(private readonly NormalizerInterface $documentationNormalizer, $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $data = $this->documentationNormalizer->normalize($object, $format, $context);
        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }

        if (!($context[self::API_GATEWAY] ?? $this->defaultContext[self::API_GATEWAY])) {
            return $data;
        }

        if (empty($data['basePath'])) {
            $data['basePath'] = '/';
        }

        foreach ($data['paths'] as $path => $operations) {
            foreach ($operations as $operation => $options) {
                if (isset($options['parameters'])) {
                    foreach ($options['parameters'] as $key => $parameter) {
                        if (!preg_match('/^[a-zA-Z0-9._$-]+$/', (string) $parameter['name'])) {
                            unset($data['paths'][$path][$operation]['parameters'][$key]);
                        }
                        if (isset($parameter['schema']['$ref']) && $this->isLocalRef($parameter['schema']['$ref'])) {
                            $data['paths'][$path][$operation]['parameters'][$key]['schema']['$ref'] = $this->normalizeRef($parameter['schema']['$ref']);
                        }
                    }
                    $data['paths'][$path][$operation]['parameters'] = array_values($data['paths'][$path][$operation]['parameters']);
                }
                if (isset($options['responses'])) {
                    foreach ($options['responses'] as $statusCode => $response) {
                        if (isset($response['schema']['items']['$ref']) && $this->isLocalRef($response['schema']['items']['$ref'])) {
                            $data['paths'][$path][$operation]['responses'][$statusCode]['schema']['items']['$ref'] = $this->normalizeRef($response['schema']['items']['$ref']);
                        }
                        if (isset($response['schema']['$ref']) && $this->isLocalRef($response['schema']['$ref'])) {
                            $data['paths'][$path][$operation]['responses'][$statusCode]['schema']['$ref'] = $this->normalizeRef($response['schema']['$ref']);
                        }
                    }
                }
            }
        }

        foreach ($data['components']['schemas'] as $definition => $options) {
            if (!isset($options['properties'])) {
                continue;
            }
            foreach ($options['properties'] as $property => $propertyOptions) {
                if (isset($propertyOptions['readOnly'])) {
                    unset($data['components']['schemas'][$definition]['properties'][$property]['readOnly']);
                }
                if (isset($propertyOptions['$ref']) && $this->isLocalRef($propertyOptions['$ref'])) {
                    $data['components']['schemas'][$definition]['properties'][$property]['$ref'] = $this->normalizeRef($propertyOptions['$ref']);
                }
                if (isset($propertyOptions['items']['$ref']) && $this->isLocalRef($propertyOptions['items']['$ref'])) {
                    $data['components']['schemas'][$definition]['properties'][$property]['items']['$ref'] = $this->normalizeRef($propertyOptions['items']['$ref']);
                }
            }
        }

        // $data['definitions'] is an instance of \ArrayObject
        foreach (array_keys($data['components']['schemas']) as $definition) {
            if (!preg_match('/^[0-9A-Za-z]+$/', (string) $definition)) {
                $data['components']['schemas'][preg_replace('/[^0-9A-Za-z]/', '', (string) $definition)] = $data['components']['schemas'][$definition];
                unset($data['components']['schemas'][$definition]);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->documentationNormalizer->supportsNormalization($data, $format);
    }

    public function getSupportedTypes($format): array
    {
        // @deprecated remove condition when support for symfony versions under 6.3 is dropped
        if (!method_exists($this->documentationNormalizer, 'getSupportedTypes')) {
            return ['*' => $this->documentationNormalizer instanceof BaseCacheableSupportsMethodInterface && $this->documentationNormalizer->hasCacheableSupportsMethod()];
        }

        return $this->documentationNormalizer->getSupportedTypes($format);
    }

    /**
     * {@inheritdoc}
     */
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

        return $this->documentationNormalizer instanceof BaseCacheableSupportsMethodInterface && $this->documentationNormalizer->hasCacheableSupportsMethod();
    }

    private function isLocalRef(string $ref): bool
    {
        return str_starts_with($ref, '#/');
    }

    private function normalizeRef(string $ref): string
    {
        $refParts = explode('/', $ref);

        $schemaName = array_pop($refParts);
        $schemaName = preg_replace('/[^0-9A-Za-z]/', '', $schemaName);
        $refParts[] = $schemaName;

        return implode('/', $refParts);
    }
}
