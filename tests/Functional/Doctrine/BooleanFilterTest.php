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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedBoolean as ConvertedBooleanDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddableDummy as EmbeddableDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddedDummy as EmbeddedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedBoolean;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\TestWith;

final class BooleanFilterTest extends ApiTestCase
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
            EmbeddedDummy::class,
            RelatedDummy::class,
            ConvertedBoolean::class,
        ];
    }

    #[TestWith(['true', 15, ['/dummies/1', '/dummies/2', '/dummies/3']])]
    #[TestWith(['1', 15, ['/dummies/1', '/dummies/2', '/dummies/3']])]
    #[TestWith(['false', 10, ['/dummies/16', '/dummies/17', '/dummies/18']])]
    #[TestWith(['0', 10, ['/dummies/16', '/dummies/17', '/dummies/18']])]
    public function testFilterDummiesByBoolean(string $value, int $expectedTotal, array $expectedIds): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 15, true);
        $this->createDummies($resource, 10, false);

        $response = self::createClient()->request('GET', '/dummies?dummyBoolean='.$value, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('/contexts/Dummy', $data['@context']);
        $this->assertSame('/dummies', $data['@id']);
        $this->assertSame('hydra:Collection', $data['@type']);
        $this->assertSame($expectedTotal, $data['hydra:totalItems']);
        $this->assertSame($expectedIds, array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']));
        $this->assertSame('hydra:PartialCollectionView', $data['hydra:view']['@type']);
        $this->assertStringContainsString('dummyBoolean='.$value, $data['hydra:view']['@id']);
    }

    #[TestWith(['true', 15, ['/embedded_dummies/1', '/embedded_dummies/2', '/embedded_dummies/3']])]
    #[TestWith(['1', 15, ['/embedded_dummies/1', '/embedded_dummies/2', '/embedded_dummies/3']])]
    #[TestWith(['false', 10, ['/embedded_dummies/16', '/embedded_dummies/17', '/embedded_dummies/18']])]
    #[TestWith(['0', 10, ['/embedded_dummies/16', '/embedded_dummies/17', '/embedded_dummies/18']])]
    public function testFilterEmbeddedDummiesByEmbeddedBoolean(string $value, int $expectedTotal, array $expectedIds): void
    {
        $embeddedDummyClass = $this->embeddedDummyClass();
        $embeddableDummyClass = $this->embeddableDummyClass();
        $this->recreateSchema([$embeddedDummyClass]);
        $this->createEmbeddedDummies($embeddedDummyClass, $embeddableDummyClass, 15, true);
        $this->createEmbeddedDummies($embeddedDummyClass, $embeddableDummyClass, 10, false);

        $response = self::createClient()->request('GET', '/embedded_dummies?embeddedDummy.dummyBoolean='.$value, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $data = $response->toArray();
        $this->assertSame('/contexts/EmbeddedDummy', $data['@context']);
        $this->assertSame('/embedded_dummies', $data['@id']);
        $this->assertSame($expectedTotal, $data['hydra:totalItems']);
        $this->assertSame($expectedIds, array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']));
    }

    public function testFilterEmbeddedDummiesByRelatedDummyEmbeddedBoolean(): void
    {
        $embeddedDummyClass = $this->embeddedDummyClass();
        $embeddableDummyClass = $this->embeddableDummyClass();
        $relatedDummyClass = $this->relatedDummyClass();
        $this->recreateSchema([$embeddedDummyClass, $relatedDummyClass]);
        $this->createEmbeddedDummiesWithRelatedDummy($embeddedDummyClass, $embeddableDummyClass, $relatedDummyClass, 15, true);
        $this->createEmbeddedDummiesWithRelatedDummy($embeddedDummyClass, $embeddableDummyClass, $relatedDummyClass, 10, false);

        $response = self::createClient()->request('GET', '/embedded_dummies?relatedDummy.embeddedDummy.dummyBoolean=true', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(15, $data['hydra:totalItems']);
        $this->assertSame(
            ['/embedded_dummies/1', '/embedded_dummies/2', '/embedded_dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    #[TestWith(['0'])]
    #[TestWith(['1'])]
    public function testCollectionIgnoresUnknownBooleanFilter(string $value): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 15, true);
        $this->createDummies($resource, 10, false);

        $response = self::createClient()->request('GET', '/dummies?unknown='.$value, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(25, $response->toArray()['hydra:totalItems']);
    }

    public function testFilterCollectionUsingNameConverter(): void
    {
        $resource = $this->isMongoDB() ? ConvertedBooleanDocument::class : ConvertedBoolean::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 5; ++$i) {
            $entity = new $resource();
            $entity->nameConverted = (bool) ($i % 2);
            $manager->persist($entity);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/converted_booleans?name_converted=false', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/converted_booleans/2', '/converted_booleans/4'], $ids);
        foreach ($data['hydra:member'] as $member) {
            $this->assertSame('ConvertedBoolean', $member['@type']);
            $this->assertFalse($member['name_converted']);
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
    private function embeddedDummyClass(): string
    {
        return $this->isMongoDB() ? EmbeddedDummyDocument::class : EmbeddedDummy::class;
    }

    /**
     * @return class-string
     */
    private function embeddableDummyClass(): string
    {
        return $this->isMongoDB() ? EmbeddableDummyDocument::class : EmbeddableDummy::class;
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
    private function createDummies(string $resource, int $nb, bool $bool): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyBoolean($bool);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $embeddedClass
     * @param class-string $embeddableClass
     */
    private function createEmbeddedDummies(string $embeddedClass, string $embeddableClass, int $nb, bool $bool): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $embeddedClass();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddable = new $embeddableClass();
            $embeddable->setDummyName('Embedded Dummy #'.$i);
            $embeddable->setDummyBoolean($bool);
            $dummy->setEmbeddedDummy($embeddable);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $embeddedClass
     * @param class-string $embeddableClass
     * @param class-string $relatedClass
     */
    private function createEmbeddedDummiesWithRelatedDummy(string $embeddedClass, string $embeddableClass, string $relatedClass, int $nb, bool $bool): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $embeddedClass();
            $dummy->setName('Embedded Dummy #'.$i);
            $embeddable = new $embeddableClass();
            $embeddable->setDummyName('Embedded Dummy #'.$i);
            $embeddable->setDummyBoolean($bool);

            $related = new $relatedClass();
            $related->setEmbeddedDummy($embeddable);

            $dummy->setRelatedDummy($related);

            $manager->persist($related);
            $manager->persist($dummy);
        }
        $manager->flush();
    }
}
