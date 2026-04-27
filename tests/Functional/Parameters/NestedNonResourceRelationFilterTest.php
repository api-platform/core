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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\NonResourceRelation as DocumentNonResourceRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ResourceWithNonResourceRelation as DocumentResourceWithNonResourceRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\NonResourceRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceWithNonResourceRelation;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests that PartialSearchFilter and other filters work correctly
 * with nested properties where the related entity is NOT an ApiResource.
 *
 * @see https://github.com/api-platform/core/issues/7916
 */
final class NestedNonResourceRelationFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ResourceWithNonResourceRelation::class];
    }

    protected function setUp(): void
    {
        $entities = $this->isMongoDB()
            ? [DocumentResourceWithNonResourceRelation::class, DocumentNonResourceRelation::class]
            : [ResourceWithNonResourceRelation::class, NonResourceRelation::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    public function testPartialSearchFilterOnNonResourceRelationProperty(): void
    {
        $response = self::createClient()->request('GET', '/resources_with_non_resource_relations?name=Electronics');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('Product A', $data['hydra:member'][0]['title']);
    }

    public function testPartialSearchFilterOnNonResourceRelationPropertyPartialMatch(): void
    {
        $response = self::createClient()->request('GET', '/resources_with_non_resource_relations?name=lect');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('Product A', $data['hydra:member'][0]['title']);
    }

    public function testPartialSearchFilterOnNonResourceRelationCategoryProperty(): void
    {
        $response = self::createClient()->request('GET', '/resources_with_non_resource_relations?category=Books');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(2, $data['hydra:member']);
    }

    public function testPartialSearchFilterOnNonResourceRelationNoMatch(): void
    {
        $response = self::createClient()->request('GET', '/resources_with_non_resource_relations?name=Nonexistent');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(0, $data['hydra:member']);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();
        $isMongoDB = $this->isMongoDB();

        $nonResourceClass = $isMongoDB ? DocumentNonResourceRelation::class : NonResourceRelation::class;
        $resourceClass = $isMongoDB ? DocumentResourceWithNonResourceRelation::class : ResourceWithNonResourceRelation::class;

        $relation1 = new $nonResourceClass();
        $relation1->setName('Electronics');
        $relation1->setCategory('Gadgets');

        $relation2 = new $nonResourceClass();
        $relation2->setName('Novel');
        $relation2->setCategory('Books');

        $relation3 = new $nonResourceClass();
        $relation3->setName('Computer');
        $relation3->setCategory('Books');

        $manager->persist($relation1);
        $manager->persist($relation2);
        $manager->persist($relation3);
        $manager->flush();

        $resource1 = new $resourceClass();
        $resource1->setTitle('Product A');
        $resource1->setNonResourceRelation($relation1);

        $resource2 = new $resourceClass();
        $resource2->setTitle('Product B');
        $resource2->setNonResourceRelation($relation2);

        $resource3 = new $resourceClass();
        $resource3->setTitle('Product C');
        $resource3->setNonResourceRelation($relation3);

        $manager->persist($resource1);
        $manager->persist($resource2);
        $manager->persist($resource3);
        $manager->flush();
    }
}
