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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EntityClassAndCustomProviderResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ResourceWithSeparatedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResourceOdm\ResourceWithSeparatedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SeparatedEntity as SeparatedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SeparatedEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SeparatedResourceTest extends ApiTestCase
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
            ResourceWithSeparatedEntity::class,
            ResourceWithSeparatedDocument::class,
            EntityClassAndCustomProviderResource::class,
        ];
    }

    public function testGetCollection(): void
    {
        $resource = $this->isMongoDB() ? SeparatedDocument::class : SeparatedEntity::class;
        $this->recreateSchema([$resource]);
        $this->createSeparatedEntities($resource, 5);

        $uri = $this->isMongoDB() ? '/separated_documents' : '/separated_entities';
        $shortName = $this->isMongoDB() ? 'SeparatedDocument' : 'SeparatedEntity';

        $response = self::createClient()->request('GET', $uri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('/contexts/'.$shortName, $data['@context']);
        $this->assertStringStartsWith($uri, $data['@id']);
        $this->assertSame('hydra:Collection', $data['@type']);
        $this->assertIsArray($data['hydra:member']);
        $this->assertIsInt($data['hydra:totalItems']);
        $this->assertArrayHasKey('hydra:view', $data);
    }

    public function testGetOrderedCollection(): void
    {
        $resource = $this->isMongoDB() ? SeparatedDocument::class : SeparatedEntity::class;
        $this->recreateSchema([$resource]);
        $this->createSeparatedEntities($resource, 5);

        $uri = $this->isMongoDB() ? '/separated_documents' : '/separated_entities';

        $response = self::createClient()->request('GET', $uri.'?order[value]=desc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertSame('5', $response->toArray()['hydra:member'][0]['value']);
    }

    public function testGetItem(): void
    {
        $resource = $this->isMongoDB() ? SeparatedDocument::class : SeparatedEntity::class;
        $this->recreateSchema([$resource]);
        $this->createSeparatedEntities($resource, 5);

        $uri = $this->isMongoDB() ? '/separated_documents/1' : '/separated_entities/1';

        self::createClient()->request('GET', $uri, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testGetAllEntityClassAndCustomProviderResources(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('EntityClassAndCustomProviderResource uses ORM stateOptions only.');
        }

        $this->recreateSchema([SeparatedEntity::class]);
        $this->createSeparatedEntities(SeparatedEntity::class, 1);

        self::createClient()->request('GET', '/entityClassAndCustomProviderResources', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testGetOneEntityClassAndCustomProviderResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('EntityClassAndCustomProviderResource uses ORM stateOptions only.');
        }

        $this->recreateSchema([SeparatedEntity::class]);
        $this->createSeparatedEntities(SeparatedEntity::class, 1);

        self::createClient()->request('GET', '/entityClassAndCustomProviderResources/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @param class-string $resource
     */
    private function createSeparatedEntities(string $resource, int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $entity = new $resource();
            $entity->value = (string) $i;
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
