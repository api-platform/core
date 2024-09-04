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

namespace ApiPlatform\Tests\Doctrine\Filter\Orm;

use ApiPlatform\Doctrine\EventListener\PurgeHttpCacheListener;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\NotAResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SearchFilterParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AliasedPropertySearchItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AliasedPropertySearchItemDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ContainNonResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyNoGetOperation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SearchFilterParameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Chrirstophe Zarebski <christophe.zarebski@gmail.com>
 */
class AliasedPropertySearchFilterTest extends ApiTestCase
{
    private function recreateSchema(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $this->getContainer()->get('mongodb' === $container->getParameter('kernel.environment') ? 'doctrine_mongodb' : 'doctrine');
        $resource = 'mongodb' === $container->getParameter('kernel.environment') ? AliasedPropertySearchItemDocument::class : AliasedPropertySearchItem::class;
        $manager = $registry->getManager();

        if ($manager instanceof EntityManagerInterface) {
            $classes = $manager->getClassMetadata($resource);
            $schemaTool = new SchemaTool($manager);
            @$schemaTool->dropSchema([$classes]);
            @$schemaTool->createSchema([$classes]);
        } else {
            $schemaManager = $manager->getSchemaManager();
            $schemaManager->dropCollections();
        }

        $datasets = [
            [
                "name" => "is_not_validated",
                "isvalidated" => false,
                "date_of_creation" => \DateTime::createFromFormat('Y-m-dTH:i:s', "1999-01-01T00:00:00"),
                "nullableBoolProperty" => null,
                "timesExecuted" => 0
            ],
            [
                "name" => "is_validated",
                "isValidated" => true,
                "dateOfCreation" => \DateTime::createFromFormat('Y-m-dTH:i:s', "1999-01-01T00:00:00"),
                "nullableBoolProperty" => null,
                "timesExecuted" => 0
            ],
            [
                "name" => "executed_10",
                "isValidated" => true,
                "dateOfCreation" => \DateTime::createFromFormat('Y-m-dTH:i:s', "1999-01-01T00:00:00"),
                "nullableBoolProperty" => null,
                "timesExecuted" => 10
            ],
            [
                "name" => "executed_20",
                "isValidated" => true,
                "dateOfCreation" => \DateTime::createFromFormat('Y-m-dTH:i:s', "1999-01-01T00:00:00"),
                "nullableBoolProperty" => null,
                "timesExecuted" => 20
            ],
            [
                "name" => "executed_30",
                "isValidated" => true,
                "dateOfCreation" => \DateTime::createFromFormat('Y-m-dTH:i:s', "1999-01-01T00:00:00"),
                "nullableBoolProperty" => null,
                "timesExecuted" => 30
            ],
            [
                "name" => "created_after",
                "isValidated" => true,
                "dateOfCreation" => \DateTime::createFromFormat('Y-m-dTH:i:s', "1999-01-02T00:00:00"),
                "nullableBoolProperty" => null,
                "timesExecuted" => 1000
            ],
            [
                "name" => "nullable_property_exists",
                "isValidated" => true,
                "dateOfCreation" => \DateTime::createFromFormat('Y-m-dTH:i:s', "1999-01-01T00:00:00"),
                "nullableBoolProperty" => true,
                "timesExecuted" => 0
            ]
        ];

        foreach ($datasets as $set) {
            foreach ($set as $property => $value) {
                $r = new $resource();
                $r->{'set'.ucfirst($property)}($value);
            }
            $manager->persist($r);
        }
        $manager->flush();
    }

    private function getEntityRoutePart(): string
    {
        $container = static::getContainer();
        return 'mongodb' === $container->getParameter('kernel.environment') ? 'aliased-property-search-items' : 'aliased-property-search-documents';
    }

    /**
     * @group aliasedPropertyFilters
     */
    public function testQuerySearchFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $this->recreateSchema();
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', $route.'?aliasedName=is_not_validated');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('is_not_validated', $a['hydra:member'][0]['name']);

        $this->assertEquals(
            [
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedName",
                    "property" => "aliasedName",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedName[]",
                    "property" => "aliasedName",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedIsValidated",
                    "property" => "aliasedIsValidated",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedDateOfCreation[before]",
                    "property" => "aliasedDateOfCreation",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedDateOfCreation[strictly_before]",
                    "property" => "aliasedDateOfCreation",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedDateOfCreation[after]",
                    "property" => "aliasedDateOfCreation",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedDateOfCreation[strictly_after]",
                    "property" => "aliasedDateOfCreation",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "exists[aliasedNullableBoolProperty]",
                    "property" => "aliasedNullableBoolProperty",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedTimesExecuted",
                    "property" => "aliasedTimesExecuted",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "aliasedTimesExecuted[]",
                    "property" => "aliasedTimesExecuted",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "customOrder[aliasedTimesExecuted]",
                    "property" => "aliasedTimesExecuted",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "timesExecuted[between]",
                    "property" => "timesExecuted",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "timesExecuted[gt]",
                    "property" => "timesExecuted",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "timesExecuted[gte]",
                    "property" => "timesExecuted",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "timesExecuted[lt]",
                    "property" => "timesExecuted",
                    "required" => false
                ],
                [
                    "@type" => "IriTemplateMapping",
                    "variable" => "timesExecuted[lte]",
                    "property" => "timesExecuted",
                    "required" => false
                ]
            ], $a['hydra:search']['hydra:mapping']
        );
    }

    /**
     * @group aliasedPropertyFilters
     */
    public function testQueryBooleanFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $this->recreateSchema();
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', $route.'?aliasedIsValidated=false');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('is_not_validated', $a['hydra:member'][0]['name']);
    }

    /**
     * @group aliasedPropertyFilters
     */
    public function testQueryNumericFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $this->recreateSchema();
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', $route.'?aliasedTimesExecuted=20');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals(20, $a['hydra:member'][0]['timesExecuted']);
    }

    /**
     * @group aliasedPropertyFilters
     */
    public function testQueryOrderFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $this->recreateSchema();
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', $route.'?customOrder[aliasedTimesExecuted]=desc');

        $a = $response->toArray();
        $this->assertCount(7, $a['hydra:member']);
        $this->assertEquals(1000, $a['hydra:member'][0]['timesExecuted']);
    }

    /**
     * @group aliasedPropertyFilters
     */
    public function testQueryRangeFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $this->recreateSchema();
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', $route.'?aliasedTimesExecuted[between]=10..30');

        $a = $response->toArray();
        $this->assertCount(3, $a['hydra:member']);
    }

    /**
     * @group aliasedPropertyFilters
     */
    public function testQueryDateFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $this->recreateSchema();
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', $route.'?aliasedDateOfCreation[strictly_after]=1999-01-01T01:01:01');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('created_after', $a['hydra:member'][0]['name']);
    }

    /**
     * @group aliasedPropertyFilters
     */
    public function testQueryExistsFilterAliasedPropertyAndResultIsCorrect(): void
    {
        $this->recreateSchema();
        $route = $this->getEntityRoutePart();

        $response = self::createClient()->request('GET', $route.'?exists[aliasedNullableBoolProperty]=true');

        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('nullable_property_exists', $a['hydra:member'][0]['name']);
    }
}
