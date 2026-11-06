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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedOutputEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Verifies the behavior of write operations declaring an `output` DTO with the ObjectMapper.
 */
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

        fwrite(\STDERR, "\n=== POST status: ".$response->getStatusCode()."\n");
        fwrite(\STDERR, "=== POST response body ===\n".substr($response->getContent(false), 0, 2000)."\n");
        fwrite(\STDERR, '=== Location header: '.var_export($response->getHeaders(false)['location'][0] ?? null, true)."\n");
        fwrite(\STDERR, '=== Content-Location header: '.var_export($response->getHeaders(false)['content-location'][0] ?? null, true)."\n");

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        // The output DTO exposes "name" but not "description"
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('description', $data, 'Response should be the output DTO, not the resource');

        // Item IRI expected on a 201, not the collection IRI
        $location = $response->getHeaders(false)['location'][0] ?? null;
        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression('~^/mapped_resource_with_outputs/\d+$~', $location, 'Location must be the item IRI');
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

        fwrite(\STDERR, "\n=== PATCH status: ".$response->getStatusCode()."\n");
        fwrite(\STDERR, "=== PATCH response body ===\n".substr($response->getContent(false), 0, 2000)."\n");

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        // The response must be the output DTO with a proper item IRI
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('description', $data, 'Response should be the output DTO, not the resource');
        $this->assertSame('/mapped_resource_with_outputs/'.$id, $data['@id']);

        // PATCH semantics: unsent fields must be preserved on the entity
        $manager = $this->getManager();
        $manager->clear();
        $persisted = $manager->getRepository(MappedOutputEntity::class)->find($id);
        $this->assertSame('updated name', $persisted->name);
        $this->assertSame('original description', $persisted->description, 'PATCH must not touch fields the client did not send');
    }
}
