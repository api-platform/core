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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Owner as DocumentOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Chicken;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ChickenCoop;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Owner;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ComparisonFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Chicken::class, ChickenCoop::class, Owner::class];
    }

    protected function setUp(): void
    {
        $entities = $this->isMongoDB()
            ? [DocumentChicken::class, DocumentChickenCoop::class, DocumentOwner::class]
            : [Chicken::class, ChickenCoop::class, Owner::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    public function testGt(): void
    {
        // gt "Bravo": names > "Bravo" alphabetically → Charlie, Delta
        $response = self::createClient()->request('GET', '/chickens?nameComparison[gt]=Bravo');
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn (array $c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Charlie', 'Delta'], $names);
    }

    public function testGte(): void
    {
        // gte "Bravo": names >= "Bravo" → Bravo, Charlie, Delta
        $response = self::createClient()->request('GET', '/chickens?nameComparison[gte]=Bravo');
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn (array $c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Bravo', 'Charlie', 'Delta'], $names);
    }

    public function testLt(): void
    {
        // lt "Charlie": names < "Charlie" → Alpha, Bravo
        $response = self::createClient()->request('GET', '/chickens?nameComparison[lt]=Charlie');
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn (array $c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Alpha', 'Bravo'], $names);
    }

    public function testLte(): void
    {
        // lte "Charlie": names <= "Charlie" → Alpha, Bravo, Charlie
        $response = self::createClient()->request('GET', '/chickens?nameComparison[lte]=Charlie');
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn (array $c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $names);
    }

    public function testCombinedGtAndLt(): void
    {
        // gt "Alpha" AND lt "Delta" → Bravo, Charlie
        $response = self::createClient()->request('GET', '/chickens?nameComparison[gt]=Alpha&nameComparison[lt]=Delta');
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn (array $c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Bravo', 'Charlie'], $names);
    }

    public function testNe(): void
    {
        // ne "Bravo": all names except "Bravo" → Alpha, Charlie, Delta
        $response = self::createClient()->request('GET', '/chickens?nameComparison[ne]=Bravo');
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn (array $c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Alpha', 'Charlie', 'Delta'], $names);
    }

    public function testNeNoMatch(): void
    {
        // ne "Unknown": no chicken has that name, so all are returned
        $response = self::createClient()->request('GET', '/chickens?nameComparison[ne]=Unknown&itemsPerPage=10');
        $this->assertResponseIsSuccessful();
        $this->assertCount(4, $response->toArray()['member']);
    }

    public function testGtNoResults(): void
    {
        // gt "ZZZZ": no name is alphabetically after "ZZZZ"
        $response = self::createClient()->request('GET', '/chickens?nameComparison[gt]=ZZZZ');
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $response->toArray()['member']);
    }

    public function testGteAllResults(): void
    {
        // gte "A": all names start with A or later → all 4
        $response = self::createClient()->request('GET', '/chickens?nameComparison[gte]=A&itemsPerPage=10');
        $this->assertResponseIsSuccessful();
        $this->assertCount(4, $response->toArray()['member']);
    }

    public function testOpenApiDocumentation(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $openApiDoc = $response->toArray();

        $parameters = $openApiDoc['paths']['/chickens']['get']['parameters'];
        $parameterNames = array_column($parameters, 'name');

        foreach (['nameComparison[gt]', 'nameComparison[gte]', 'nameComparison[lt]', 'nameComparison[lte]', 'nameComparison[ne]'] as $expectedName) {
            $this->assertContains($expectedName, $parameterNames, \sprintf('Expected parameter "%s" in OpenAPI documentation', $expectedName));
        }

        $comparisonParams = array_filter($parameters, static fn (array $p): bool => str_starts_with($p['name'], 'nameComparison['));
        foreach ($comparisonParams as $param) {
            $this->assertSame('query', $param['in']);
        }
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $chickenClass = $this->isMongoDB() ? DocumentChicken::class : Chicken::class;
        $coopClass = $this->isMongoDB() ? DocumentChickenCoop::class : ChickenCoop::class;
        $ownerClass = $this->isMongoDB() ? DocumentOwner::class : Owner::class;

        $owner = new $ownerClass();
        $owner->setName('TestOwner');
        $manager->persist($owner);
        $manager->flush();

        $coop = new $coopClass();
        $manager->persist($coop);

        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            $chicken = new $chickenClass();
            $chicken->setName($name);
            $chicken->setEan('000000000000');
            $chicken->setChickenCoop($coop);
            $chicken->setOwner($owner);
            $coop->addChicken($chicken);
            $manager->persist($chicken);
        }

        $manager->flush();
    }
}
