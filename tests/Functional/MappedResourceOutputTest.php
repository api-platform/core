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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedOutputEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MappedResourceOutputTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [MappedResourceWithOutput::class];
    }

    public function testPostWithOutputDtoReturnsOutputDto(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        $this->recreateSchema([MappedOutputEntity::class]);

        $client = self::createClient();
        $response = $client->request('POST', '/mapped_resource_with_outputs', [
            'json' => ['name' => 'a name', 'description' => 'a description'],
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('description', $data, 'Response should be the output DTO, not the resource');

        $location = $response->getHeaders(false)['location'][0] ?? null;
        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression('~^/mapped_resource_with_outputs/\d+$~', $location, 'Location must be the item IRI');
        $this->assertSame($location.'.jsonld', $response->getHeaders(false)['content-location'][0] ?? null);
        $this->assertSame($location, $data['@id'] ?? null, '@id must be the item IRI');
    }

    public function testPatchWithOutputDtoPreservesUnsentFields(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        $this->recreateSchema([MappedOutputEntity::class]);

        $manager = $this->getManager();
        $entity = new MappedOutputEntity();
        $entity->name = 'original name';
        $entity->description = 'original description';
        $manager->persist($entity);
        $manager->flush();
        $id = $entity->id;
        $manager->clear();

        $client = self::createClient();
        $response = $client->request('PATCH', '/mapped_resource_with_outputs/'.$id, [
            'headers' => ['content-type' => 'application/merge-patch+json'],
            'json' => ['name' => 'updated name'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        $this->assertSame('updated name', $data['name']);
        $this->assertArrayNotHasKey('description', $data, 'Response should be the output DTO, not the resource');

        $this->assertNotSame('/mapped_resource_with_outputs/'.$id, $data['@id'] ?? null);
        $this->assertSame('/mapped_resource_with_outputs/'.$id.'.jsonld', $response->getHeaders(false)['content-location'][0] ?? null);

        $manager = $this->getManager();
        $manager->clear();
        $persisted = $manager->getRepository(MappedOutputEntity::class)->find($id);
        $this->assertSame('updated name', $persisted->name);
        $this->assertSame('original description', $persisted->description, 'PATCH must not touch fields the client did not send');
    }

    public function testPutWithOutputDtoCreatesWith201(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        $this->recreateSchema([MappedOutputEntity::class]);

        $response = self::createClient()->request('PUT', '/mapped_resource_with_outputs/1', [
            'json' => ['name' => 'a name', 'description' => 'a description'],
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);
        $this->assertSame('a name', $data['name']);
        $this->assertArrayNotHasKey('description', $data, 'Response should be the output DTO, not the resource');
    }
}
