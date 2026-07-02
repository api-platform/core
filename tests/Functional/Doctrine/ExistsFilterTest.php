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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedString as ConvertedStringDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedString;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ExistsFilterTest extends ApiTestCase
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
            Dummy::class,
            RelatedDummy::class,
            ConvertedString::class,
        ];
    }

    public function testCollectionWhereScalarPropertyDoesNotExist(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummiesWithBoolean($resource, 15, true);

        $response = self::createClient()->request('GET', '/dummies?exists[dummyBoolean]=0', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $data = $response->toArray();
        $this->assertSame(0, $data['hydra:totalItems']);
        $this->assertSame([], $data['hydra:member']);
    }

    public function testCollectionWhereScalarPropertyDoesExist(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummiesWithBoolean($resource, 15, true);

        $response = self::createClient()->request('GET', '/dummies?exists[dummyBoolean]=1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(15, $data['hydra:totalItems']);
        $this->assertCount(3, $data['hydra:member']);
        foreach ($data['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('#^/dummies/(1|2|3)$#', $member['@id']);
        }
    }

    public function testCollectionWithEmptyRelationCollection(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource, $this->relatedDummyClass()]);
        $this->createDummiesWithRelated($resource, 3, 0);
        $this->createDummiesWithRelated($resource, 2, 3);

        $response = self::createClient()->request('GET', '/dummies?exists[relatedDummies]=0', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(3, $data['hydra:totalItems']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/dummies/1', '/dummies/2', '/dummies/3'], $ids);
    }

    public function testCollectionWithNonEmptyRelationCollection(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource, $this->relatedDummyClass()]);
        $this->createDummiesWithRelated($resource, 3, 0);
        $this->createDummiesWithRelated($resource, 2, 3);

        $response = self::createClient()->request('GET', '/dummies?exists[relatedDummies]=1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/dummies/4', '/dummies/5'], $ids);
    }

    public function testCollectionFilteredUsingNameConverter(): void
    {
        $resource = $this->isMongoDB() ? ConvertedStringDocument::class : ConvertedString::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 4; ++$i) {
            $entity = new $resource();
            $entity->nameConverted = ($i % 2) ? "name#$i" : null;
            $manager->persist($entity);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/converted_strings?exists[name_converted]=true', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/converted_strings/1', '/converted_strings/3'], $ids);
        foreach ($data['hydra:member'] as $member) {
            $this->assertSame('ConvertedString', $member['@type']);
            $this->assertMatchesRegularExpression('/^name#(1|3)$/', $member['name_converted']);
        }
    }

    /**
     * @return class-string
     */
    private function dummyClass(): string
    {
        return $this->isMongoDB() ? DummyDocument::class : Dummy::class;
    }

    /**
     * @return class-string
     */
    private function relatedDummyClass(): string
    {
        return $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;
    }

    /**
     * @param class-string $resource
     */
    private function createDummiesWithBoolean(string $resource, int $nb, bool $bool): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDummyBoolean($bool);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $resource
     */
    private function createDummiesWithRelated(string $resource, int $nb, int $nbRelated): void
    {
        $manager = $this->getManager();
        $relatedDummyClass = $this->relatedDummyClass();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            for ($j = 1; $j <= $nbRelated; ++$j) {
                $relatedDummy = new $relatedDummyClass();
                $relatedDummy->setName('RelatedDummy'.$j.$i);
                $relatedDummy->setAge((int) ($j.$i));
                $manager->persist($relatedDummy);
                $dummy->addRelatedDummy($relatedDummy);
            }
            $manager->persist($dummy);
        }
        $manager->flush();
    }
}
