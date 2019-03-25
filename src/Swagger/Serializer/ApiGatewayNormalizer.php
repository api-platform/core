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

namespace ApiPlatform\Core\Swagger\Serializer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

    private $documentationNormalizer;
    private $defaultContext = [self::API_GATEWAY => false];

    public function __construct(NormalizerInterface $documentationNormalizer, $defaultContext = [])
    {
        $this->documentationNormalizer = $documentationNormalizer;
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->documentationNormalizer->normalize($object, $format, $context);
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
                        if (!preg_match('/^[a-zA-Z0-9._$-]+$/', $parameter['name'])) {
                            unset($data['paths'][$path][$operation]['parameters'][$key]);
                        }
                        if (isset($parameter['schema']['$ref']) && !preg_match('/^#\/definitions\/[A-z]+$/', $parameter['schema']['$ref'])) {
                            $data['paths'][$path][$operation]['parameters'][$key]['schema']['$ref'] = str_replace(['-', '_'], '', $parameter['schema']['$ref']);
                        }
                    }
                    $data['paths'][$path][$operation]['parameters'] = array_values($data['paths'][$path][$operation]['parameters']);
                }
                if (isset($options['responses'])) {
                    foreach ($options['responses'] as $statusCode => $response) {
                        if (isset($response['schema']['items']['$ref']) && !preg_match('/^#\/definitions\/[A-z]+$/', $response['schema']['items']['$ref'])) {
                            $data['paths'][$path][$operation]['responses'][$statusCode]['schema']['items']['$ref'] = str_replace(['-', '_'], '', $response['schema']['items']['$ref']);
                        }
                        if (isset($response['schema']['$ref']) && !preg_match('/^#\/definitions\/[A-z]+$/', $response['schema']['$ref'])) {
                            $data['paths'][$path][$operation]['responses'][$statusCode]['schema']['$ref'] = str_replace(['-', '_'], '', $response['schema']['$ref']);
                        }
                    }
                }
            }
        }

        foreach ($data['definitions'] as $definition => $options) {
            if (!isset($options['properties'])) {
                continue;
            }
            foreach ($options['properties'] as $property => $propertyOptions) {
                if (isset($propertyOptions['readOnly'])) {
                    unset($data['definitions'][$definition]['properties'][$property]['readOnly']);
                }
                if (isset($propertyOptions['$ref']) && !preg_match('/^#\/definitions\/[A-z]+$/', $propertyOptions['$ref'])) {
                    $data['definitions'][$definition]['properties'][$property]['$ref'] = str_replace(['-', '_'], '', $propertyOptions['$ref']);
                }
                if (isset($propertyOptions['items']['$ref']) && !preg_match('/^#\/definitions\/[A-z]+$/', $propertyOptions['items']['$ref'])) {
                    $data['definitions'][$definition]['properties'][$property]['items']['$ref'] = str_replace(['-', '_'], '', $propertyOptions['items']['$ref']);
                }
            }
        }

        // $data['definitions'] is an instance of \ArrayObject
        foreach (array_keys($data['definitions']->getArrayCopy()) as $definition) {
            if (!preg_match('/^[A-z]+$/', (string) $definition)) {
                $data['definitions'][str_replace(['-', '_'], '', (string) $definition)] = $data['definitions'][$definition];
                unset($data['definitions'][$definition]);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->documentationNormalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return $this->documentationNormalizer instanceof CacheableSupportsMethodInterface && $this->documentationNormalizer->hasCacheableSupportsMethod();
    }
}
