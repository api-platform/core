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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Chicken as DocumentChicken;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ChickenCoop as DocumentChickenCoop;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Chicken;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ChickenCoop;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;

final class IriFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ChickenCoop::class, Chicken::class];
    }

    public function testIriFilter(): void
    {
        $client = $this->createClient();

        $res = $client->request('GET', '/chickens?chickenCoop=/chicken_coops/2')->toArray();
        $this->assertCount(1, $res['member']);
        $this->assertEquals('/chicken_coops/2', $res['member'][0]['chickenCoop']);

        $res = $client->request('GET', '/chickens?chickenCoop=/chicken_coops/595')->toArray();
        $this->assertCount(0, $res['member']);
    }

    public function testIriFilterMultiple(): void
    {
        $client = $this->createClient();
        $res = $client->request('GET', '/chickens?chickenCoop[]=/chicken_coops/2&chickenCoop[]=/chicken_coops/1')->toArray();
        $this->assertCount(2, $res['member']);
    }

    public function testIriFilterWithOneToManyRelation(): void
    {
        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?chickenIri=/chickens/1');
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(1, $filteredCoops, 'Expected 1 coop for URL /chicken_coops?chickenIri=/chickens/1');

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = $this->createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude'], $allChickenNames, 'The chicken names in coops do not match the expected values.');

        $res = $client->request('GET', '/chicken_coops?chickenIri=/chickens/595')->toArray();
        $this->assertCount(0, $res['member']);
    }

    public function testIriFilterWithOneToManyRelationWithMultiple(): void
    {
        $response = $this->createClient()->request('GET', '/chicken_coops?chickenIri[]=/chickens/1&chickenIri[]=/chickens/2');
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(2, $filteredCoops, 'Expected 2 coops for URL /chicken_coops?chickenIri[]=/chickens/1&chickenIri[]=/chickens/2');

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = $this->createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude', 'Henriette'], $allChickenNames, 'The chicken names in coops do not match the expected values.');
    }

    public function testIriFilterWithOneToManyRelationWithPropertyPlaceholder(): void
    {
        $client = $this->createClient();

        $propertyName = $this->isMongoDB() ? 'chickenReferences' : 'chickens';
        $response = $client->request('GET', '/chicken_coops?searchChickenIri['.$propertyName.']=/chickens/1');
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(1, $filteredCoops, 'Expected 1 coop for URL /chicken_coops?searchChickenIri['.$propertyName.']=/chickens/1');

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = $this->createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude'], $allChickenNames, 'The chicken names in coops do not match the expected values.');

        $res = $client->request('GET', '/chicken_coops?searchChickenIri['.$propertyName.']=/chickens/595')->toArray();
        $this->assertCount(0, $res['member']);
    }

    public function testIriFilterWithOneToManyRelationWithMultiplePropertyPlaceholder(): void
    {
        $response = $this->createClient()->request('GET', '/chicken_coops?searchChickenIri[chickens][]=/chickens/1&searchChickenIri[chickens][]=/chickens/2');
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(2, $filteredCoops, 'Expected 2 coops for URL /chicken_coops?searchChickenIri[chickens][]=/chickens/1&searchChickenIri[chickens][]=/chickens/2');

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $chickenIri) {
                $chickenResponse = $this->createClient()->request('GET', $chickenIri);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude', 'Henriette'], $allChickenNames, 'The chicken names in coops do not match the expected values.');
    }

    public function testIriFilterThrowsExceptionWhenPropertyIsMissing(): void
    {
        $response = self::createClient()->request('GET', '/chickens?chickenCoopNoProperty=/chicken_coops/1');
        $this->assertResponseStatusCodeSame(400);

        $responseData = $response->toArray(false);

        $this->assertSame('An error occurred', $responseData['hydra:title']);
        $this->assertSame($responseData['hydra:description'], $responseData['detail']);
        $this->assertStringContainsString(
            'The filter parameter with key "chickenCoopNoProperty" must specify a property',
            $responseData['detail']
        );
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema([$this->isMongoDB() ? DocumentChicken::class : Chicken::class, $this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class]);
        $this->loadFixtures();
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $chickenCoop1 = new ($this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class)();
        $chickenCoop2 = new ($this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class)();

        $chicken1 = new ($this->isMongoDB() ? DocumentChicken::class : Chicken::class)();
        $chicken1->setName('Gertrude');
        $chicken1->setChickenCoop($chickenCoop1);

        $chicken2 = new ($this->isMongoDB() ? DocumentChicken::class : Chicken::class)();
        $chicken2->setName('Henriette');
        $chicken2->setChickenCoop($chickenCoop2);

        $chickenCoop1->addChicken($chicken1);
        $chickenCoop2->addChicken($chicken2);

        if ($this->isMongoDB()) {
            $chickenCoop1->addChickenReference($chicken1);
            $chickenCoop2->addChickenReference($chicken2);
        }

        $manager->persist($chicken1);
        $manager->persist($chicken2);
        $manager->persist($chickenCoop1);
        $manager->persist($chickenCoop2);
        $manager->flush();
    }
}
