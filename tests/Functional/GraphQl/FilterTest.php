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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedOwner as ConvertedOwnerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedRelated as ConvertedRelatedDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCar as DummyCarDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCarColor as DummyCarColorDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\Common\Collections\ArrayCollection;

final class FilterTest extends ApiTestCase
{
    use GraphQlTestTrait;
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
            ConvertedOwner::class,
            ConvertedRelated::class,
            DummyCar::class,
            DummyCarColor::class,
        ];
    }

    public function testBooleanFilter(): void
    {
        $this->recreateDummiesAndRelated();
        $manager = $this->getManager();
        $true = $this->newDummy();
        $true->setName('Dummy #1');
        $true->setDummyBoolean(true);
        $manager->persist($true);

        $false = $this->newDummy();
        $false->setName('Dummy #2');
        $false->setDummyBoolean(false);
        $manager->persist($false);
        $manager->flush();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(dummyBoolean: false) {
                edges { node { id dummyBoolean } }
              }
            }
            QUERY);

        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(1, $edges);
        $this->assertFalse($edges[0]['node']['dummyBoolean']);
    }

    public function testExistsFilter(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummies(3);
        $this->seedDummiesWithRelatedDummy(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(exists: [{relatedDummy: true}]) {
                edges {
                  node {
                    id
                    relatedDummy { name }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(2, $edges);
        $this->assertArrayHasKey('name', $edges[0]['node']['relatedDummy']);
    }

    public function testDateFilter(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithDate(3);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(dummyDate: [{after: "2015-04-02"}]) {
                edges { node { id dummyDate } }
              }
            }
            QUERY);

        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(1, $edges);
        $this->assertSame('2015-04-02', $edges[0]['node']['dummyDate']);
    }

    public function testSearchFilterOnName(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummies(10);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(name: "#2") {
                edges { node { id name } }
              }
            }
            QUERY);

        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(1, $edges);
        $this->assertSame('/dummies/2', $edges[0]['node']['id']);
    }

    public function testSearchFilterWithIntOnNestedCollection(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesEachWithRelatedDummies(4, 3);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(name: "Dummy #1") {
                totalCount
                edges {
                  node {
                    name
                    relatedDummies(age: 31) {
                      totalCount
                      edges {
                        node { id name age }
                      }
                    }
                  }
                }
              }
            }
            QUERY);

        $data = $response->toArray()['data']['dummies'];
        $this->assertSame(1, $data['totalCount']);
        $this->assertSame(1, $data['edges'][0]['node']['relatedDummies']['totalCount']);
        $this->assertSame('31', (string) $data['edges'][0]['node']['relatedDummies']['edges'][0]['node']['age']);
    }

    public function testSearchFilterWithNameConverter(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummies(10);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(name_converted: "Converted 2") {
                edges { node { id name name_converted } }
              }
            }
            QUERY);

        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(1, $edges);
        $this->assertSame('/dummies/2', $edges[0]['node']['id']);
        $this->assertSame('Converted 2', $edges[0]['node']['name_converted']);
    }

    public function testSearchFilterWithNameConverterOnNestedProperty(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? ConvertedOwnerDocument::class : ConvertedOwner::class,
            $this->isMongoDB() ? ConvertedRelatedDocument::class : ConvertedRelated::class,
        ]);
        $this->seedConvertedOwners(20);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              convertedOwners(name_converted__name_converted: "Converted 2") {
                edges {
                  node {
                    id
                    name_converted { name_converted }
                  }
                }
              }
            }
            QUERY);

        $edges = $response->toArray()['data']['convertedOwners']['edges'];
        $this->assertCount(2, $edges);
        $this->assertSame('/converted_owners/2', $edges[0]['node']['id']);
        $this->assertSame('Converted 2', $edges[0]['node']['name_converted']['name_converted']);
        $this->assertSame('/converted_owners/20', $edges[1]['node']['id']);
        $this->assertSame('Converted 20', $edges[1]['node']['name_converted']['name_converted']);
    }

    public function testSearchFilterOnNestedCollection(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesEachWithRelatedDummies(3, 3);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies {
                edges {
                  node {
                    id
                    relatedDummies(name: "RelatedDummy13") {
                      edges { node { id name } }
                    }
                  }
                }
              }
            }
            QUERY);

        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(0, $edges[0]['node']['relatedDummies']['edges']);
        $this->assertCount(0, $edges[1]['node']['relatedDummies']['edges']);
        $this->assertCount(1, $edges[2]['node']['relatedDummies']['edges']);
        $this->assertSame('RelatedDummy13', $edges[2]['node']['relatedDummies']['edges'][0]['node']['name']);
    }

    public function testNestedCollectionFilter(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyCarDocument::class : DummyCar::class,
            $this->isMongoDB() ? DummyCarColorDocument::class : DummyCarColor::class,
        ]);
        $this->seedDummyCarWithColors();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyCar(id: "/dummy_cars/1") {
                id
                colors(prop: "blue") {
                  edges { node { id prop } }
                }
              }
            }
            QUERY);

        $edges = $response->toArray()['data']['dummyCar']['colors']['edges'];
        $this->assertCount(1, $edges);
        $this->assertSame('blue', $edges[0]['node']['prop']);
    }

    public function testRelatedSearchFilter(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesEachWithRelatedDummies(1, 2);
        $this->seedDummiesEachWithRelatedDummies(1, 3);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(relatedDummies__name: "RelatedDummy31") {
                edges { node { id } }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray()['data']['dummies']['edges']);
    }

    public function testOrderByNestedProperty(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(order: [{relatedDummy__name: "DESC"}]) {
                edges {
                  node {
                    name
                    relatedDummy { id name }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertSame('Dummy #2', $edges[0]['node']['name']);
        $this->assertSame('Dummy #1', $edges[1]['node']['name']);
    }

    public function testMultiKeyOrderRespectsArgumentOrder(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithSimilarProperties();

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(order: [{description: "ASC"}, {name: "ASC"}]) {
                edges {
                  node { id name description }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertSame('baz', $edges[0]['node']['name']);
        $this->assertSame('bar', $edges[0]['node']['description']);
        $this->assertSame('foo', $edges[1]['node']['name']);
        $this->assertSame('bar', $edges[1]['node']['description']);
    }

    public function testRelatedSearchFilterMultiValueExact(): void
    {
        $this->recreateDummiesAndRelated();
        $this->seedDummiesWithRelatedDummy(3);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummies(relatedDummy__name_list: ["RelatedDummy #1", "RelatedDummy #2"]) {
                edges {
                  node {
                    id
                    name
                    relatedDummy { name }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $edges = $response->toArray()['data']['dummies']['edges'];
        $this->assertCount(2, $edges);
        $this->assertSame('RelatedDummy #1', $edges[0]['node']['relatedDummy']['name']);
        $this->assertSame('RelatedDummy #2', $edges[1]['node']['relatedDummy']['name']);
    }

    private function recreateDummiesAndRelated(): void
    {
        $this->recreateSchema([
            $this->isMongoDB() ? DummyDocument::class : Dummy::class,
            $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class,
        ]);
    }

    private function newDummy(): object
    {
        $class = $this->isMongoDB() ? DummyDocument::class : Dummy::class;

        return new $class();
    }

    private function newRelated(): object
    {
        $class = $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;

        return new $class();
    }

    private function seedDummies(int $count): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->setDummy('SomeDummyTest'.$i);
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->nameConverted = 'Converted '.$i;
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesWithDate(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            if ($count !== $i) {
                $dummy->setDummyDate(new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC')));
            }
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesWithRelatedDummy(int $count): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $related = $this->newRelated();
            $related->setName('RelatedDummy #'.$i);

            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));
            $dummy->nameConverted = "Converted $i";
            $dummy->setRelatedDummy($related);

            $manager->persist($related);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesEachWithRelatedDummies(int $count, int $nbRelated): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dummy = $this->newDummy();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($count - $i));

            for ($j = 1; $j <= $nbRelated; ++$j) {
                $related = $this->newRelated();
                $related->setName('RelatedDummy'.$j.$i);
                $related->setAge((int) ($j.$i));
                $manager->persist($related);

                $dummy->addRelatedDummy($related);
            }
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedDummiesWithSimilarProperties(): void
    {
        $manager = $this->getManager();
        foreach ([
            ['foo', 'bar'],
            ['baz', 'qux'],
            ['foo', 'qux'],
            ['baz', 'bar'],
        ] as [$name, $description]) {
            $dummy = $this->newDummy();
            $dummy->setName($name);
            $dummy->setDescription($description);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    private function seedConvertedOwners(int $count): void
    {
        $relatedClass = $this->isMongoDB() ? ConvertedRelatedDocument::class : ConvertedRelated::class;
        $ownerClass = $this->isMongoDB() ? ConvertedOwnerDocument::class : ConvertedOwner::class;
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $related = new $relatedClass();
            $related->nameConverted = 'Converted '.$i;

            $owner = new $ownerClass();
            $owner->nameConverted = $related;

            $manager->persist($related);
            $manager->persist($owner);
        }
        $manager->flush();
    }

    private function seedDummyCarWithColors(): void
    {
        $manager = $this->getManager();
        $carClass = $this->isMongoDB() ? DummyCarDocument::class : DummyCar::class;
        $colorClass = $this->isMongoDB() ? DummyCarColorDocument::class : DummyCarColor::class;

        $car = new $carClass();
        $car->setName('mustli');
        $car->setCanSell(true);
        $car->setAvailableAt(new \DateTime());
        $manager->persist($car);
        $manager->flush();

        if (\is_object($car->getId())) {
            $manager->persist($car->getId());
            $manager->flush();
        }

        $red = new $colorClass();
        $red->setProp('red');
        $red->setCar($car);
        $manager->persist($red);
        $manager->flush();

        $blue = new $colorClass();
        $blue->setProp('blue');
        $blue->setCar($car);
        $manager->persist($blue);
        $manager->flush();

        $car->setColors(new ArrayCollection([$red, $blue]));
        $manager->persist($car);
        $manager->flush();
    }
}
