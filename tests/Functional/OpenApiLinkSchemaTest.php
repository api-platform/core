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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\LinkSchema\LinkSchemaChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\LinkSchema\LinkSchemaParent;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OpenApiLinkSchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            LinkSchemaParent::class,
            LinkSchemaChild::class,
        ];
    }

    public function testLinkSchemaIsInferredFromLinkedProperty(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();

        $this->assertArrayHasKey('/link_schema_parents/{parentId}/children/{id}', $json['paths']);
        $parameters = $json['paths']['/link_schema_parents/{parentId}/children/{id}']['get']['parameters'];

        $this->assertCount(2, $parameters);
        $this->assertSame(['parentId', 'id'], array_column($parameters, 'name'));
        $this->assertSame($parameters[0]['name'], 'parentId');
        $this->assertSame($parameters[1]['name'], 'id');

        $parentParam = $parameters[0];
        $idParam = $parameters[1];

        $this->assertNotNull($parentParam);
        $this->assertSame('path', $parentParam['in']);
        $this->assertTrue($parentParam['required']);
        $this->assertSame(['type' => 'string', 'format' => 'uuid'], $parentParam['schema']);

        $this->assertNotNull($idParam);
        $this->assertSame('path', $idParam['in']);
        $this->assertTrue($idParam['required']);
        $this->assertSame(['type' => 'integer'], $idParam['schema']);
    }

    public function testLinkSchemaIsInferredFromLinkedNonIdentifierProperty(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();

        $this->assertArrayHasKey('/link_schema_parents_by_code/{parentCode}/children/{id}', $json['paths']);
        $byCodeParameters = $json['paths']['/link_schema_parents_by_code/{parentCode}/children/{id}']['get']['parameters'];

        $this->assertSame($byCodeParameters[0]['name'], 'parentCode');
        $parentCodeParam = $byCodeParameters[0];

        $this->assertNotNull($parentCodeParam);
        $this->assertSame('path', $parentCodeParam['in']);
        $this->assertTrue($parentCodeParam['required']);
        $this->assertSame(['type' => 'integer'], $parentCodeParam['schema']);
    }
}
