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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\FirstResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceOdm;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceSourceOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithInput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithRelationRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SecondResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MappedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntitySourceOnly;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceWithRelationEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceWithRelationRelatedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SameEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MappingTest extends ApiTestCase
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
            MappedResource::class,
            MappedResourceOdm::class,
            FirstResource::class,
            SecondResource::class,
            MappedResourceWithRelation::class,
            MappedResourceWithRelationRelated::class,
            MappedResourceWithInput::class,
            MappedResourceSourceOnly::class,
        ];
    }

    public function testShouldMapBetweenResourceAndEntity(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([MappedEntity::class]);
        $this->loadFixtures();
        $r = self::createClient()->request('GET', $this->isMongoDB() ? 'mapped_resource_odms' : 'mapped_resources');
        $this->assertJsonContains(['member' => [
            ['username' => 'B0 A0'],
            ['username' => 'B1 A1'],
            ['username' => 'B2 A2'],
        ]]);

        $r = self::createClient()->request('POST', $this->isMongoDB() ? 'mapped_resource_odms' : 'mapped_resources', ['json' => ['username' => 'so yuka']]);
        $this->assertJsonContains(['username' => 'so yuka']);

        $manager = $this->getManager();
        $repo = $manager->getRepository($this->isMongoDB() ? MappedDocument::class : MappedEntity::class);
        $persisted = $repo->findOneBy(['id' => $r->toArray()['id']]);
        $this->assertSame('so', $persisted->getFirstName());
        $this->assertSame('yuka', $persisted->getLastName());

        $uri = $r->toArray()['@id'];
        self::createClient()->request('GET', $uri);
        $this->assertJsonContains(['username' => 'so yuka']);

        $r = self::createClient()->request('PATCH', $uri, ['json' => ['username' => 'ba zar'], 'headers' => ['content-type' => 'application/merge-patch+json']]);
        $this->assertJsonContains(['username' => 'ba zar']);
    }

    public function testShouldMapToTheCorrectResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([SameEntity::class]);
        $manager = $this->getManager();
        $e = new SameEntity();
        $e->setName('foo');
        $manager->persist($e);
        $manager->flush();

        self::createClient()->request('GET', '/seconds');
        $this->assertJsonContains(['hydra:member' => [
            ['name' => 'foo', 'extra' => 'field'],
        ]]);
    }

    public function testMapPutAllowCreate(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB is not tested');
        }

        $this->recreateSchema([MappedResourceWithRelationEntity::class, MappedResourceWithRelationRelatedEntity::class]);
        $manager = $this->getManager();

        $e = new MappedResourceWithRelationRelatedEntity();
        $e->name = 'test';
        $manager->persist($e);
        $manager->flush();

        self::createClient()->request('PUT', '/mapped_resource_with_relations/4', [
            'json' => [
                '@id' => '/mapped_resource_with_relations/4',
                'relation' => '/mapped_resource_with_relation_relateds/'.$e->getId(),
            ],
            'headers' => [
                'content-type' => 'application/ld+json',
            ],
        ]);

        $this->assertJsonContains([
            '@context' => '/contexts/MappedResourceWithRelation',
            '@id' => '/mapped_resource_with_relations/4',
            '@type' => 'MappedResourceWithRelation',
            'id' => '4',
            'relationName' => 'test',
            'relation' => '/mapped_resource_with_relation_relateds/1',
        ]);
    }

    public function testShouldNotMapWhenInput(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        $this->recreateSchema([MappedEntity::class]);
        $this->loadFixtures();
        $r = self::createClient()->request('POST', 'mapped_resource_with_input', [
            'headers' => [
                'content-type' => 'application/ld+json',
            ],
            'json' => ['name' => 'test', 'id' => '1'],
        ]);

        $this->assertJsonContains(['username' => 'test']);
    }

    public function testShouldMapWithSourceOnly(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested');
        }

        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([MappedEntitySourceOnly::class]);
        $manager = $this->getManager();

        for ($i = 0; $i < 10; ++$i) {
            $e = new MappedEntitySourceOnly();
            $e->setLastName('A'.$i);
            $e->setFirstName('B'.$i);
            $manager->persist($e);
        }

        $manager->flush();

        $r = self::createClient()->request('GET', '/mapped_resource_source_onlies');
        $this->assertJsonContains(['member' => [
            ['username' => 'B0 A0'],
            ['username' => 'B1 A1'],
            ['username' => 'B2 A2'],
        ]]);

        $r = self::createClient()->request('POST', 'mapped_resource_source_onlies', ['json' => ['username' => 'so yuka']]);
        $this->assertJsonContains(['username' => 'so yuka']);

        $manager = $this->getManager();
        $repo = $manager->getRepository(MappedEntitySourceOnly::class);
        $persisted = $repo->findOneBy(['id' => $r->toArray()['id']]);
        $this->assertSame('so', $persisted->getFirstName());
        $this->assertSame('yuka', $persisted->getLastName());

        $uri = $r->toArray()['@id'];
        self::createClient()->request('GET', $uri);
        $this->assertJsonContains(['username' => 'so yuka']);

        $r = self::createClient()->request('PATCH', $uri, ['json' => ['username' => 'ba zar'], 'headers' => ['content-type' => 'application/merge-patch+json']]);
        $this->assertJsonContains(['username' => 'ba zar']);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        for ($i = 0; $i < 10; ++$i) {
            $e = $manager instanceof DocumentManager ? new MappedDocument() : new MappedEntity();
            $e->setLastName('A'.$i);
            $e->setFirstName('B'.$i);
            $manager->persist($e);
        }

        $manager->flush();
    }
}
