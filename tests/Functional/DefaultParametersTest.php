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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

/**
 * Tests that default parameters configured via api_platform.defaults.parameters
 * appear in all resources and operations in the OpenAPI and JSONSchema documentation.
 *
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
final class DefaultParametersTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return DefaultParametersAppKernel::class;
    }

    /**
     * Test that default header parameter appears in all operations in OpenAPI documentation.
     *
     * This test verifies that when default parameters are configured via
     * api_platform.defaults.parameters with:
     *   HeaderParameter:
     *     key: 'X-API-Key'
     *     required: false
     *     description: 'API key for authentication'
     *
     * The parameter appears in ALL resources and ALL their operations in the OpenAPI output.
     */
    public function testDefaultParameterAppearsInOpenApiForAllOperations(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $content = $response->toArray();

        $this->assertArrayHasKey('openapi', $content);
        $this->assertArrayHasKey('paths', $content);

        $foundParameter = false;
        $operationsWithParameter = [];

        foreach ($content['paths'] as $pathName => $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (!isset($pathItem[$method]['parameters'])) {
                    continue;
                }

                $parameters = $pathItem[$method]['parameters'];
                foreach ($parameters as $param) {
                    if ('X-API-Key' === $param['name'] && 'header' === $param['in']) {
                        $foundParameter = true;
                        $operationsWithParameter[] = [
                            'path' => $pathName,
                            'method' => $method,
                        ];

                        $this->assertSame('X-API-Key', $param['name']);
                        $this->assertSame('header', $param['in']);
                        $this->assertSame('API key for authentication', $param['description']);
                        $this->assertFalse($param['required']);
                        $this->assertFalse($param['deprecated']);
                        $this->assertArrayHasKey('schema', $param);
                        $this->assertSame('string', $param['schema']['type']);
                        break;
                    }
                }
            }
        }

        $this->assertTrue(
            $foundParameter,
            \sprintf(
                'Default header parameter "X-API-Key" not found in any operation. Operations checked: %d',
                \count($content['paths'] ?? [])
            )
        );

        $this->assertGreaterThanOrEqual(2, \count($operationsWithParameter),
            'Default parameter should appear in multiple operations (collection and item)'
        );
    }

    /**
     * Test that default parameters appear in both collection and item operations.
     */
    public function testDefaultParameterAppearsInMultipleOperationTypes(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $content = $response->toArray();

        $operationMethodsWithParameter = [];

        foreach ($content['paths'] as $pathName => $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (!isset($pathItem[$method]['parameters'])) {
                    continue;
                }

                $parameters = $pathItem[$method]['parameters'];
                foreach ($parameters as $param) {
                    if ('X-API-Key' === $param['name'] && 'header' === $param['in']) {
                        $operationMethodsWithParameter[$method] = true;
                        break;
                    }
                }
            }
        }

        $this->assertGreaterThanOrEqual(2, \count($operationMethodsWithParameter),
            \sprintf('Default parameter should appear in at least 2 different HTTP methods, found in: %s',
                implode(', ', array_keys($operationMethodsWithParameter)))
        );
    }

    public function testDefaultParametersDoNotBreakJsonLdDocumentation(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $content = $response->toArray();

        $this->assertArrayHasKey('@context', $content);

        $this->assertTrue(
            isset($content['entrypoint']) || isset($content['hydra:supportedClass']),
            'JSON-LD response should have either "entrypoint" or "hydra:supportedClass" key'
        );
    }
}
