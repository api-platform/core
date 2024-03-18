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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SearchFilterParameter;
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
        $registry = $this->getContainer()->get('doctrine');
        $entityManager = $registry->getManagerForClass(SearchFilterParameter::class);

        foreach (['foo', 'foo', 'foo', 'bar', 'bar'] as $t) {
            $s = new SearchFilterParameter();
            $s->setFoo($t);
            $entityManager->persist($s);
        }
        $entityManager->flush();

        $response = self::createClient()->request('GET', 'search_filter_parameter?search=bar');
        $a = $response->toArray();
        $this->assertCount(2, $a['hydra:member']);
        $this->assertEquals('bar', $a['hydra:member'][0]['foo']);
        $this->assertEquals('bar', $a['hydra:member'][1]['foo']);

        $this->assertArraySubset(['hydra:search' => [
            'hydra:template' => '/search_filter_parameter{?search}',
            'hydra:mapping' => [
                ['@type' => 'IriTemplateMapping', 'variable' => 'search', 'property' => 'foo'],
            ],
        ]], $a);
    }

    private function recreateSchema(array $options = []): void
    {
        self::bootKernel($options);

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        /** @var ClassMetadata[] $classes */
        $classes = $manager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($manager);

        @$schemaTool->dropSchema($classes);
        @$schemaTool->createSchema($classes);
    }

}
