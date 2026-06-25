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

namespace ApiPlatform\Tests\Functional\Parameters\Legacy;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Legacy\SearchFilterParameter as SearchFilterParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Legacy\SearchFilterParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression coverage for the deprecated SearchFilter exercised through the custom
 * SearchFilterValueTransformer / SearchTextAndDateFilter wrappers and #[ApiFilter] aliases.
 * Canonical scalar/search coverage lives in DoctrineTest (ProductWithQueryParameter).
 * Remove together with the deprecated filter in 6.0.
 */
#[Group('legacy')]
final class SearchFilterParameterLegacyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

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
        $route = 'legacy_search_filter_parameter';
        $response = self::createClient()->request('GET', $route.'?foo=bar');
        $a = $response->toArray();
        $this->assertCount(2, $a['hydra:member']);
        $this->assertEquals('bar', $a['hydra:member'][0]['foo']);
        $this->assertEquals('bar', $a['hydra:member'][1]['foo']);

        $this->assertArraySubset(['hydra:search' => [
            'hydra:template' => \sprintf('/%s{?foo,fooAlias,q,order[id],order[foo],searchPartial[foo],searchExact[foo],searchOnTextAndDate[foo],searchOnTextAndDate[createdAt][before],searchOnTextAndDate[createdAt][strictly_before],searchOnTextAndDate[createdAt][after],searchOnTextAndDate[createdAt][strictly_after],search[foo],search[createdAt],id,createdAt}', $route),
        ]], $a);

        $this->assertArraySubset(['@type' => 'IriTemplateMapping', 'variable' => 'fooAlias', 'property' => 'foo'], $a['hydra:search']['hydra:mapping'][1]);

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
        if ($_SERVER['EVENT_LISTENERS_BACKWARD_COMPATIBILITY_LAYER'] ?? false) {
            $this->markTestSkipped('Parameters are not supported in BC mode.');
        }

        $resource = $this->isMongoDB() ? SearchFilterParameterDocument::class : SearchFilterParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);
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
        static::bootKernel();
        $resource = $this->isMongoDB() ? SearchFilterParameterDocument::class : SearchFilterParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);
        $route = 'legacy_search_filter_parameter';
        $response = self::createClient()->request('GET', $route.'?foo=baz');
        $a = $response->toArray();
        $this->assertEquals($a['hydra:member'][0]['foo'], 'baz');
    }

    #[DataProvider('partialFilterParameterProviderForSearchFilterParameter')]
    public function testPartialSearchFilterWithSearchFilterParameter(string $url, int $expectedCount, array $expectedFoos): void
    {
        $resource = $this->isMongoDB() ? SearchFilterParameterDocument::class : SearchFilterParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadFixtures($resource);

        $response = self::createClient()->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedCount, $url));

        $foos = array_map(static fn ($item) => $item['foo'], $filteredItems);
        sort($foos);
        sort($expectedFoos);

        $this->assertSame($expectedFoos, $foos, 'The "foo" values do not match the expected values.');
    }

    public static function partialFilterParameterProviderForSearchFilterParameter(): \Generator
    {
        // Fixtures Recap (from loadFixtures with SearchFilterParameter):
        // 3x foo = 'foo'
        // 2x foo = 'bar'
        // 1x foo = 'baz'

        yield 'partial match on foo (fo -> 3x foo)' => [
            '/legacy_search_filter_parameter?searchPartial[foo]=fo',
            3,
            ['foo', 'foo', 'foo'],
        ];

        yield 'partial match on foo (ba -> 2x bar, 1x baz)' => [
            '/legacy_search_filter_parameter?searchPartial[foo]=ba',
            3,
            ['bar', 'bar', 'baz'],
        ];

        yield 'partial match on foo (az -> 1x baz)' => [
            '/legacy_search_filter_parameter?searchPartial[foo]=az',
            1,
            ['baz'],
        ];
    }

    private function loadFixtures(string $resourceClass): void
    {
        $container = static::$kernel->getContainer();
        $registry = $this->isMongoDB() ? $container->get('doctrine_mongodb') : $container->get('doctrine');
        $manager = $registry->getManager();
        $date = new \DateTimeImmutable('2024-01-21');
        foreach (['foo', 'foo', 'foo', 'bar', 'bar', 'baz'] as $t) {
            $s = new $resourceClass();
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
