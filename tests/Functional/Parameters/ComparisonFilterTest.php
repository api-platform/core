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
        $this->recreateSchema([Chicken::class, ChickenCoop::class, Owner::class]);
        $this->loadFixtures();
    }

    /**
     * @return array<string, int> name => id mapping
     */
    private function getChickenIds(): array
    {
        $response = self::createClient()->request('GET', '/chickens?itemsPerPage=10');
        $members = $response->toArray()['member'];
        $ids = [];
        foreach ($members as $member) {
            $ids[$member['name']] = $member['id'];
        }

        return $ids;
    }

    public function testGt(): void
    {
        $ids = $this->getChickenIds();
        // gt: id > second chicken should return third and fourth
        $response = self::createClient()->request('GET', '/chickens?idComparison[gt]='.$ids['Bravo']);
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn ($c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Charlie', 'Delta'], $names);
    }

    public function testGte(): void
    {
        $ids = $this->getChickenIds();
        $response = self::createClient()->request('GET', '/chickens?idComparison[gte]='.$ids['Bravo']);
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn ($c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Bravo', 'Charlie', 'Delta'], $names);
    }

    public function testLt(): void
    {
        $ids = $this->getChickenIds();
        $response = self::createClient()->request('GET', '/chickens?idComparison[lt]='.$ids['Charlie']);
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn ($c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Alpha', 'Bravo'], $names);
    }

    public function testLte(): void
    {
        $ids = $this->getChickenIds();
        $response = self::createClient()->request('GET', '/chickens?idComparison[lte]='.$ids['Charlie']);
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn ($c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $names);
    }

    public function testBetween(): void
    {
        $ids = $this->getChickenIds();
        $response = self::createClient()->request('GET', '/chickens?idComparison[between]='.$ids['Bravo'].'..'.$ids['Charlie']);
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn ($c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Bravo', 'Charlie'], $names);
    }

    public function testBetweenEqualValues(): void
    {
        $ids = $this->getChickenIds();
        $response = self::createClient()->request('GET', '/chickens?idComparison[between]='.$ids['Bravo'].'..'.$ids['Bravo']);
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn ($c) => $c['name'], $response->toArray()['member']);
        $this->assertSame(['Bravo'], $names);
    }

    public function testCombinedGtAndLt(): void
    {
        $ids = $this->getChickenIds();
        $response = self::createClient()->request('GET', '/chickens?idComparison[gt]='.$ids['Alpha'].'&idComparison[lt]='.$ids['Delta']);
        $this->assertResponseIsSuccessful();
        $names = array_map(static fn ($c) => $c['name'], $response->toArray()['member']);
        sort($names);
        $this->assertSame(['Bravo', 'Charlie'], $names);
    }

    public function testGtNoResults(): void
    {
        $response = self::createClient()->request('GET', '/chickens?idComparison[gt]=999999');
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $response->toArray()['member']);
    }

    public function testGteAllResults(): void
    {
        $ids = $this->getChickenIds();
        $minId = min($ids);
        $response = self::createClient()->request('GET', '/chickens?idComparison[gte]='.$minId.'&itemsPerPage=10');
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

        foreach (['idComparison[gt]', 'idComparison[gte]', 'idComparison[lt]', 'idComparison[lte]', 'idComparison[between]'] as $expectedName) {
            $this->assertContains($expectedName, $parameterNames, \sprintf('Expected parameter "%s" in OpenAPI documentation', $expectedName));
        }

        $comparisonParams = array_filter($parameters, static fn ($p) => str_starts_with($p['name'], 'idComparison['));
        foreach ($comparisonParams as $param) {
            $this->assertSame('query', $param['in']);
            $this->assertArrayHasKey('schema', $param);

            if ('idComparison[between]' === $param['name']) {
                $this->assertSame('string', $param['schema']['type']);
                $this->assertArrayHasKey('pattern', $param['schema']);
            } else {
                $this->assertSame('string', $param['schema']['type']);
            }
        }
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $owner = new Owner();
        $owner->setName('TestOwner');
        $manager->persist($owner);

        $coop = new ChickenCoop();
        $manager->persist($coop);

        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            $chicken = new Chicken();
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
