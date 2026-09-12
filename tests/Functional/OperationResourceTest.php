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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OperationResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [OperationResource::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());
    }

    private function seedOne(): void
    {
        self::createClient()->request('POST', '/operation_resources', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['identifier' => 1, 'dummy' => null, 'name' => 'string'],
        ]);
    }

    public function testCreateOperationResource(): void
    {
        self::createClient()->request('POST', '/operation_resources', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['identifier' => 1, 'dummy' => null, 'name' => 'string'],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testPatchOperationResource(): void
    {
        $this->seedOne();

        self::createClient()->request('PATCH', '/operation_resources/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Patched'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/OperationResource',
            '@id' => '/operation_resources/1',
            '@type' => 'OperationResource',
            'identifier' => 1,
            'name' => 'Patched',
        ]);
    }

    public function testPutOperationResource(): void
    {
        $this->seedOne();

        self::createClient()->request('PUT', '/operation_resources/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'Modified'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Location', '/operation_resources/1.jsonld');
        $this->assertJsonEquals([
            '@context' => '/contexts/OperationResource',
            '@id' => '/operation_resources/1',
            '@type' => 'OperationResource',
            'identifier' => 1,
            'name' => 'Modified',
        ]);
    }
}
