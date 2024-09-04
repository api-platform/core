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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AliasedPropertySearchItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AliasedPropertySearchItemDocument;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Before;

/**
 * @author Christophe Zarebski <christophe.zarebski@gmail.com>
 */
class AliasedPropertySearchFilterTest extends ApiTestCase
{
    public static bool $hasSetup = false;

    #[Before]
    protected function createEntities(): void
    {
        if (self::$hasSetup) {
            return;
        }

        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $isMongo = 'mongodb' === $container->getParameter('kernel.environment');

        $class = $isMongo ? AliasedPropertySearchItemDocument::class : AliasedPropertySearchItem::class;
        $classes = [];

        $classes[] = $manager->getClassMetadata($class);

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->createSchema($classes);

        $datasets = [
            [
                'name' => 'is_not_validated',
                'isValidated' => false,
                'dateOfCreation' => \DateTime::createFromFormat('Y-m-d\TH:i:s', '1999-01-01T00:00:00'),
                'nullableBoolProperty' => null,
                'timesExecuted' => 0,
            ],
            [
                'name' => 'is_validated',
                'isValidated' => true,
                'dateOfCreation' => \DateTime::createFromFormat('Y-m-d\TH:i:s', '1999-01-01T00:00:00'),
                'nullableBoolProperty' => null,
                'timesExecuted' => 0,
            ],
            [
                'name' => 'executed_10',
                'isValidated' => true,
                'dateOfCreation' => \DateTime::createFromFormat('Y-m-d\TH:i:s', '1999-01-01T00:00:00'),
                'nullableBoolProperty' => null,
                'timesExecuted' => 10,
            ],
            [
                'name' => 'executed_20',
                'isValidated' => true,
                'dateOfCreation' => \DateTime::createFromFormat('Y-m-d\TH:i:s', '1999-01-01T00:00:00'),
                'nullableBoolProperty' => null,
                'timesExecuted' => 20,
            ],
            [
                'name' => 'executed_30',
                'isValidated' => true,
                'dateOfCreation' => \DateTime::createFromFormat('Y-m-d\TH:i:s', '1999-01-01T00:00:00'),
                'nullableBoolProperty' => null,
                'timesExecuted' => 30,
            ],
            [
                'name' => 'created_after',
                'isValidated' => true,
                'dateOfCreation' => \DateTime::createFromFormat('Y-m-d\TH:i:s', '1999-01-02T00:00:00'),
                'nullableBoolProperty' => null,
                'timesExecuted' => 1000,
            ],
            [
                'name' => 'nullable_property_exists',
                'isValidated' => true,
                'dateOfCreation' => \DateTime::createFromFormat('Y-m-d\TH:i:s', '1999-01-01T00:00:00'),
                'nullableBoolProperty' => true,
                'timesExecuted' => 0,
            ],
        ];

        $r = $isMongo ? new AliasedPropertySearchItemDocument() : new AliasedPropertySearchItem();

        foreach ($datasets as $set) {
            foreach ($set as $property => $value) {
                $r->{'set'.ucfirst($property)}($value);
            }
            $manager->persist($r);
        }
        $manager->flush();

        self::$hasSetup = true;
    }

    private function getEntityRoutePart(): string
    {
        $container = static::getContainer();

        return  'mongodb' === $container->getParameter('kernel.environment')? 'aliased-property-search-documents' : 'aliased-property-search-items' ;
    }

    #[Group('aliasedPropertyFilters')]
    public function testQuerySearchFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', '/' . $route . '?aliasedName=is_not_validated');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('is_not_validated', $a['hydra:member'][0]['name']);

        $this->assertEquals(
            [
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedName',
                    'property' => 'aliasedName',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedName[]',
                    'property' => 'aliasedName',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedIsValidated',
                    'property' => 'aliasedIsValidated',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedDateOfCreation[before]',
                    'property' => 'aliasedDateOfCreation',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedDateOfCreation[strictly_before]',
                    'property' => 'aliasedDateOfCreation',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedDateOfCreation[after]',
                    'property' => 'aliasedDateOfCreation',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedDateOfCreation[strictly_after]',
                    'property' => 'aliasedDateOfCreation',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'exists[aliasedNullableBoolProperty]',
                    'property' => 'aliasedNullableBoolProperty',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedTimesExecuted',
                    'property' => 'aliasedTimesExecuted',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'aliasedTimesExecuted[]',
                    'property' => 'aliasedTimesExecuted',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'customOrder[aliasedTimesExecuted]',
                    'property' => 'aliasedTimesExecuted',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'timesExecuted[between]',
                    'property' => 'timesExecuted',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'timesExecuted[gt]',
                    'property' => 'timesExecuted',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'timesExecuted[gte]',
                    'property' => 'timesExecuted',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'timesExecuted[lt]',
                    'property' => 'timesExecuted',
                    'required' => false,
                ],
                [
                    '@type' => 'IriTemplateMapping',
                    'variable' => 'timesExecuted[lte]',
                    'property' => 'timesExecuted',
                    'required' => false,
                ],
            ], $a['hydra:search']['hydra:mapping']
        );
    }

    #[Group('aliasedPropertyFilters')]
    public function testQueryBooleanFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', '/' . $route . '?aliasedIsValidated=false');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('is_not_validated', $a['hydra:member'][0]['name']);
    }

    #[Group('aliasedPropertyFilters')]
    public function testQueryNumericFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', '/' . $route . '?aliasedTimesExecuted=20');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals(20, $a['hydra:member'][0]['timesExecuted']);
    }

    #[Group('aliasedPropertyFilters')]
    public function testQueryOrderFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', '/' . $route . '?customOrder[aliasedTimesExecuted]=desc');

        $a = $response->toArray();
        $this->assertCount(7, $a['hydra:member']);
        $this->assertEquals(1000, $a['hydra:member'][0]['timesExecuted']);
    }

    #[Group('aliasedPropertyFilters')]
    public function testQueryRangeFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', '/' . $route . '?aliasedTimesExecuted[between]=10..30');

        $a = $response->toArray();
        $this->assertCount(3, $a['hydra:member']);
    }

    #[Group('aliasedPropertyFilters')]
    public function testQueryDateFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', '/' . $route . '?aliasedDateOfCreation[strictly_after]=1999-01-01T01:01:01');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('created_after', $a['hydra:member'][0]['name']);
    }

    #[Group('aliasedPropertyFilters')]
    public function testQueryExistsFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', '/' . $route . '?exists[aliasedNullableBoolProperty]=true');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('nullable_property_exists', $a['hydra:member'][0]['name']);
    }

    public static function getResources(): array
    {
        return [];
    }
}
