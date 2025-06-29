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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceOdm;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MappedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntity;
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
        return [MappedResource::class, MappedResourceOdm::class];
    }

    public function testShouldMapBetweenResourceAndEntity(): void
    {
        if (!$this->getContainer()->has('object_mapper')) {
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
