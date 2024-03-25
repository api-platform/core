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

namespace ApiPlatform\Tests\Parameter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SearchFilterParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SearchFilterParameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class ParameterTests extends ApiTestCase
{
    public function testWithGroupFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?groups[]=b');
        $this->assertArraySubset(['b' => 'bar'], $response->toArray());
        $response = self::createClient()->request('GET', 'with_parameters/1?groups[]=b&groups[]=a');
        $this->assertArraySubset(['a' => 'foo', 'b' => 'bar'], $response->toArray());
    }

    public function testWithGroupProvider(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?group[]=b&group[]=a');
        $this->assertArraySubset(['a' => 'foo', 'b' => 'bar'], $response->toArray());
    }

    public function testWithServiceFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?properties[]=a');
        $this->assertArraySubset(['a' => 'foo'], $response->toArray());
    }

    public function testWithServiceProvider(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?service=blabla');
        $this->assertArrayNotHasKey('a', $response->toArray());
    }

    public function testWithHeader(): void
    {
        self::createClient()->request('GET', 'with_parameters/1?service=blabla', ['headers' => ['auth' => 'foo']]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testHydraTemplate(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters_collection');
        $this->assertArraySubset(['hydra:search' => [
            'hydra:template' => '/with_parameters_collection{?hydra}',
            'hydra:mapping' => [
                ['@type' => 'IriTemplateMapping', 'variable' => 'hydra', 'property' => 'a', 'required' => true],
            ],
        ]], $response->toArray());
    }

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
            'hydra:template' => sprintf('/%s{?foo,order[order[id]],order[order[foo]],searchPartial[foo],searchExact[foo],searchOnTextAndDate[foo],searchOnTextAndDate[createdAt][before],searchOnTextAndDate[createdAt][strictly_before],searchOnTextAndDate[createdAt][after],searchOnTextAndDate[createdAt][strictly_after],q}', $route),
            'hydra:mapping' => [
                ['@type' => 'IriTemplateMapping', 'variable' => 'foo', 'property' => 'foo'],
            ],
        ]], $a);

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
