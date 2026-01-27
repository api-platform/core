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

namespace ApiPlatform\Tests\Functional\Uuid;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\UuidFilterWithCustomSchema;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class UuidFilterWithCustomSchemaTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            UuidFilterWithCustomSchema::class,
        ];
    }

    public function testGetOpenApiDescriptionWhenNoCustomerSchema(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $json = $response->toArray();

        self::assertContains(
            [
                'name' => 'id',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            array_map(static fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1]), $json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'])
        );

        self::assertContains(
            [
                'name' => 'id[]',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'uuid',
                    ],
                ],
                'style' => 'deepObject',
                'explode' => true,
            ],
            array_map(static fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1, 'style' => 1, 'explode' => 1]), $json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'])
        );
    }

    public function testGetOpenApiDescriptionWhenSchemaIsOnlyString(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $json = $response->toArray();

        self::assertContains(
            [
                'name' => 'idfoo',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            array_map(static fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1]), $json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'])
        );

        self::assertNotContains(
            'idfoo[]',
            array_column($json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'], 'name')
        );
    }

    public function testGetOpenApiDescriptionWhenSchemaIsOnlyArray(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $json = $response->toArray();

        self::assertNotContains(
            'idbar',
            array_column($json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'], 'name')
        );
        self::assertContains(
            [
                'name' => 'idbar[]',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'uuid',
                    ],
                ],
                'style' => 'deepObject',
                'explode' => true,
            ],
            array_map(static fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1, 'style' => 1, 'explode' => 1]), $json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'])
        );
    }

    public function testGetOpenApiDescriptionIsOneOfArrayOrString(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $json = $response->toArray();

        self::assertContains(
            [
                'name' => 'idquz',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            array_map(static fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1]), $json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'])
        );

        self::assertContains(
            [
                'name' => 'idquz[]',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'uuid',
                    ],
                ],
                'style' => 'deepObject',
                'explode' => true,
            ],
            array_map(static fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1, 'style' => 1, 'explode' => 1]), $json['paths']['/uuid_filter_with_custom_schemas']['get']['parameters'])
        );
    }

    protected function tearDown(): void
    {
        if ($this->isMongoDB()) {
            return;
        }

        $this->recreateSchema(static::getResources());

        parent::tearDown();
    }
}
