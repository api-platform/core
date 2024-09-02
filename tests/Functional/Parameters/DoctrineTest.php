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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SearchFilterParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SearchFilterParameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class DoctrineTest extends ApiTestCase
{
    public function testDoctrineEntitySearchFilter(): void
    {
        $this->recreateSchema();
        $container = static::getContainer();
        $route = 'mongodb' === $container->getParameter('kernel.environment') ? 'search_filter_parameter_document' : 'search_filter_parameter';
        $response = self::createClient()->request('GET', $route.'?foo=bar');
        $a = $response->toArray();
        $this->assertCount(2, $a['hydra:member']);
        $this->assertEquals('bar', $a['hydra:member'][0]['foo']);
        $this->assertEquals('bar', $a['hydra:member'][1]['foo']);

        $this->assertArraySubset(['hydra:search' => [
            'hydra:template' => \sprintf('/%s{?foo,fooAlias,order[order[id]],order[order[foo]],searchPartial[foo],searchExact[foo],searchOnTextAndDate[foo],searchOnTextAndDate[createdAt][before],searchOnTextAndDate[createdAt][strictly_before],searchOnTextAndDate[createdAt][after],searchOnTextAndDate[createdAt][strictly_after],q}', $route),
            'hydra:mapping' => [
                ['@type' => 'IriTemplateMapping', 'variable' => 'foo', 'property' => 'foo'],
            ],
        ]], $a);

        $response = self::createClient()->request('GET', $route.'?fooAlias=baz');
        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('baz', $a['hydra:member'][0]['foo']);

        $response = self::createClient()->request('GET', $route.'?order[foo]=asc');
        $this->assertEquals($response->toArray()['hydra:member'][0]['foo'], 'bar');
        $response = self::createClient()->request('GET', $route.'?order[foo]=desc');
        $this->assertEquals($response->toArray()['hydra:member'][0]['foo'], 'foo');

        $response = self::createClient()->request('GET', $route.'?searchPartial[foo]=az');
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(1, $members);
        $this->assertArraySubset(['foo' => 'baz'], $members[0]);

        $response = self::createClient()->request('GET', $route.'?searchOnTextAndDate[foo]=bar&searchOnTextAndDate[createdAt][before]=2024-01-21');
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(1, $members);
        $this->assertArraySubset(['foo' => 'bar', 'createdAt' => '2024-01-21T00:00:00+00:00'], $members[0]);
    }

    /**
     * @group legacy
     */
    public function testGraphQl(): void
    {
        if ($_SERVER['EVENT_LISTENERS_BACKWARD_COMPATIBILITY_LAYER'] ?? false) {
            $this->markTestSkipped('Parameters are not supported in BC mode.');
        }

        $this->recreateSchema();
        $container = static::getContainer();
        $object = 'mongodb' === $container->getParameter('kernel.environment') ? 'searchFilterParameterDocuments' : 'searchFilterParameters';
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('{ %s(foo: "bar") { edges { node { id foo createdAt } } } }', $object),
        ]]);
        $this->assertEquals('bar', $response->toArray()['data'][$object]['edges'][0]['node']['foo']);

        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('{ %s(searchPartial: {foo: "az"}) { edges { node { id foo createdAt } } } }', $object),
        ]]);
        $this->assertEquals('baz', $response->toArray()['data'][$object]['edges'][0]['node']['foo']);

        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('{ %s(searchExact: {foo: "baz"}) { edges { node { id foo createdAt } } } }', $object),
        ]]);
        $this->assertEquals('baz', $response->toArray()['data'][$object]['edges'][0]['node']['foo']);

        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('{ %s(searchOnTextAndDate: {foo: "bar", createdAt: {before: "2024-01-21"}}) { edges { node { id foo createdAt } } } }', $object),
        ]]);
        $this->assertArraySubset(['foo' => 'bar', 'createdAt' => '2024-01-21T00:00:00+00:00'], $response->toArray()['data'][$object]['edges'][0]['node']);
    }

    /**
     * @param array<string, mixed> $options kernel options
     */
    private function recreateSchema(array $options = []): void
    {
        self::bootKernel($options);

        $container = static::getContainer();
        $registry = $this->getContainer()->get('mongodb' === $container->getParameter('kernel.environment') ? 'doctrine_mongodb' : 'doctrine');
        $resource = 'mongodb' === $container->getParameter('kernel.environment') ? SearchFilterParameterDocument::class : SearchFilterParameter::class;
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

        $date = new \DateTimeImmutable('2024-01-21');
        foreach (['foo', 'foo', 'foo', 'bar', 'bar', 'baz'] as $t) {
            $s = new $resource();
            $s->setFoo($t);
            if ('bar' === $t) {
                $s->setCreatedAt($date);
                $date = new \DateTimeImmutable('2024-01-22');
            }

            $manager->persist($s);
        }
        $manager->flush();
    }
}
