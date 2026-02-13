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

namespace ApiPlatform\Tests\Functional\OpenApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Test that global default parameters are applied to ALL resources in OpenAPI spec.
 *
 * When parameters are defined in api_platform.defaults.parameters with class names as keys,
 * they should appear in the OpenAPI documentation for EVERY resource, not just specific ones.
 */
class GlobalDefaultsParametersOpenApiTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Book::class,
            Dummy::class,
        ];
    }

    /**
     * Test that global HeaderParameter with description appears in schema.
     *
     * If we configured global parameters like:
     * ```yaml
     * defaults:
     *     parameters:
     *         'ApiPlatform\Metadata\HeaderParameter':
     *             description: 'A unique request identifier'
     * ```
     *
     * Then this parameter should appear in OpenAPI for all resources.
     */
    public function testGlobalHeaderParameterAppearsInSchema(): void
    {
        $globalHeaderParameterDescription = 'A unique request identifier';
        $globalHeaderParameterKey = 'X-Request-ID';

        $bookResponse = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $bookRes = $bookResponse->toArray();

        $bookParameters = $bookRes['paths']['/books']['get']['parameters'];
        $this->assertTrue(isset($bookParameters));

        $bookParametersHeader = $bookParameters[4];
        $this->assertSame($globalHeaderParameterDescription, $bookParametersHeader['description']);
        $this->assertSame($globalHeaderParameterKey, $bookParametersHeader['name']);

        $dummyResponse = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $dummyRes = $dummyResponse->toArray();

        $dummyParameters = $dummyRes['paths']['/dummies/{id}']['get']['parameters'];
        $this->assertTrue(isset($dummyParameters));

        $dummyParametersHeader = $dummyParameters[1];
        $this->assertSame($globalHeaderParameterDescription, $dummyParametersHeader['description']);
        $this->assertSame($globalHeaderParameterKey, $dummyParametersHeader['name']);
    }
}
