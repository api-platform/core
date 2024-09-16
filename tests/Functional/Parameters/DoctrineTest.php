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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SearchFilterParameter as SearchFilterParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SearchFilterParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DoctrineTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SearchFilterParameter::class];
    }

    public function testDoctrineEntitySearchFilter(): void
    {
        $resource = $this->isMongoDB() ? SearchFilterParameterDocument::class : SearchFilterParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);
        $route = 'search_filter_parameter';
        $response = self::createClient()->request('GET', $route.'?foo=bar');
        $a = $response->toArray();
        $this->assertCount(2, $a['hydra:member']);
        $this->assertEquals('bar', $a['hydra:member'][0]['foo']);
        $this->assertEquals('bar', $a['hydra:member'][1]['foo']);

        $this->assertArraySubset(['hydra:search' => [
            'hydra:template' => \sprintf('/%s{?foo,fooAlias,order[order[id]],order[order[foo]],searchPartial[foo],searchExact[foo],searchOnTextAndDate[foo],searchOnTextAndDate[createdAt][before],searchOnTextAndDate[createdAt][strictly_before],searchOnTextAndDate[createdAt][after],searchOnTextAndDate[createdAt][strictly_after],q,id,createdAt}', $route),
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

    public function testGraphQl(): void
    {
        $this->recreateSchema([SearchFilterParameter::class]);
        $this->loadFixtures($this->isMongoDB() ? SearchFilterParameterDocument::class : SearchFilterParameter::class);
        $object = 'searchFilterParameters';
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

    public function testPropertyPlaceholderFilter(): void
    {
        $resource = $this->isMongoDB() ? SearchFilterParameterDocument::class : SearchFilterParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);
        $route = 'search_filter_parameter';
        $response = self::createClient()->request('GET', $route.'?foo=baz');
        $a = $response->toArray();
        $this->assertEquals($a['hydra:member'][0]['foo'], 'baz');
    }

    /**
     * @param class-string $resource
     */
    private function loadFixtures(string $resource): void
    {
        $manager = $this->getManager();
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
