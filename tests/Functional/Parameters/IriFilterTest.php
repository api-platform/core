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

    /**
     * @return array{chickenIris: string[], coopIris: string[]}
     */
    private function getIris(): array
    {
        $client = self::createClient();
        $chickens = $client->request('GET', '/chickens')->toArray()['member'];
        $coops = $client->request('GET', '/chicken_coops')->toArray()['member'];

        $chickenIris = [];
        foreach ($chickens as $c) {
            $chickenIris[$c['name']] = '/chickens/'.$c['id'];
        }

        $coopIris = array_map(static fn ($c) => '/chicken_coops/'.$c['id'], $coops);

        return ['chickenIris' => $chickenIris, 'coopIris' => $coopIris];
    }

    public function testIriFilter(): void
    {
        $iris = $this->getIris();
        $client = $this->createClient();

        $res = $client->request('GET', '/chickens?chickenCoop='.$iris['coopIris'][1])->toArray();
        $this->assertCount(1, $res['member']);
        $this->assertEquals($iris['coopIris'][1], $res['member'][0]['chickenCoop']);

        $res = $client->request('GET', '/chickens?chickenCoop=/chicken_coops/595')->toArray();
        $this->assertCount(0, $res['member']);
    }

    public function testIriFilterMultiple(): void
    {
        $iris = $this->getIris();
        $client = $this->createClient();
        $res = $client->request('GET', '/chickens?chickenCoop[]='.$iris['coopIris'][1].'&chickenCoop[]='.$iris['coopIris'][0])->toArray();
        $this->assertCount(2, $res['member']);
    }

    public function testIriFilterWithOneToManyRelation(): void
    {
        $iris = $this->getIris();
        $chickenIri = $iris['chickenIris']['Gertrude'];
        $client = $this->createClient();

        $response = $client->request('GET', '/chicken_coops?chickenIri='.$chickenIri);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(1, $filteredCoops);

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $ci) {
                $chickenResponse = $this->createClient()->request('GET', $ci);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude'], $allChickenNames);

        $res = $client->request('GET', '/chicken_coops?chickenIri=/chickens/595')->toArray();
        $this->assertCount(0, $res['member']);
    }

    public function testIriFilterWithOneToManyRelationWithMultiple(): void
    {
        $iris = $this->getIris();
        $chicken1Iri = $iris['chickenIris']['Gertrude'];
        $chicken2Iri = $iris['chickenIris']['Henriette'];

        $response = $this->createClient()->request('GET', '/chicken_coops?chickenIri[]='.$chicken1Iri.'&chickenIri[]='.$chicken2Iri);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(2, $filteredCoops);

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $ci) {
                $chickenResponse = $this->createClient()->request('GET', $ci);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude', 'Henriette'], $allChickenNames);
    }

    public function testIriFilterWithOneToManyRelationWithPropertyPlaceholder(): void
    {
        $iris = $this->getIris();
        $chickenIri = $iris['chickenIris']['Gertrude'];
        $client = $this->createClient();

        $propertyName = $this->isMongoDB() ? 'chickenReferences' : 'chickens';
        $response = $client->request('GET', '/chicken_coops?searchChickenIri['.$propertyName.']='.$chickenIri);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(1, $filteredCoops);

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $ci) {
                $chickenResponse = $this->createClient()->request('GET', $ci);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude'], $allChickenNames);

        $res = $client->request('GET', '/chicken_coops?searchChickenIri['.$propertyName.']=/chickens/595')->toArray();
        $this->assertCount(0, $res['member']);
    }

    public function testIriFilterWithOneToManyRelationWithMultiplePropertyPlaceholder(): void
    {
        $iris = $this->getIris();
        $chicken1Iri = $iris['chickenIris']['Gertrude'];
        $chicken2Iri = $iris['chickenIris']['Henriette'];
        $propertyName = $this->isMongoDB() ? 'chickenReferences' : 'chickens';

        $response = $this->createClient()->request('GET', '/chicken_coops?searchChickenIri['.$propertyName.'][]='.$chicken1Iri.'&searchChickenIri['.$propertyName.'][]='.$chicken2Iri);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredCoops = $responseData['member'];

        $this->assertCount(2, $filteredCoops);

        $allChickenNames = [];
        foreach ($filteredCoops as $coop) {
            foreach ($coop['chickens'] as $ci) {
                $chickenResponse = $this->createClient()->request('GET', $ci);
                $chickenData = $chickenResponse->toArray();
                $allChickenNames[] = $chickenData['name'];
            }
        }

        sort($allChickenNames);
        $this->assertSame(['Gertrude', 'Henriette'], $allChickenNames);
    }

    public function testIriFilterThrowsExceptionWhenPropertyIsMissing(): void
    {
        $iris = $this->getIris();
        $response = self::createClient()->request('GET', '/chickens?chickenCoopNoProperty='.$iris['coopIris'][0]);
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
