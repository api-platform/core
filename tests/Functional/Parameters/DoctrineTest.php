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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\FilterWithStateOptions;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SearchFilterParameter as SearchFilterParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterWithStateOptionsEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ProductWithQueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SearchFilterParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class DoctrineTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SearchFilterParameter::class, FilterWithStateOptions::class, ProductWithQueryParameter::class];
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
        $route = 'search_filter_parameter';
        $response = self::createClient()->request('GET', $route.'?foo=baz');
        $a = $response->toArray();
        $this->assertEquals($a['hydra:member'][0]['foo'], 'baz');
    }

    public function testStateOptions(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->recreateSchema([FilterWithStateOptionsEntity::class]);
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        $d = new \DateTimeImmutable();
        $manager->persist(new FilterWithStateOptionsEntity(dummyDate: $d, name: 'current'));
        $manager->persist(new FilterWithStateOptionsEntity(name: 'null'));
        $manager->persist(new FilterWithStateOptionsEntity(dummyDate: $d->add(\DateInterval::createFromDateString('1 day')), name: 'after'));
        $manager->flush();
        $response = self::createClient()->request('GET', 'filter_with_state_options?date[before]='.$d->format('Y-m-d'));
        $a = $response->toArray();
        $this->assertEquals('/filter_with_state_options{?date[before],date[strictly_before],date[after],date[strictly_after]}', $a['hydra:search']['hydra:template']);
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('current', $a['hydra:member'][0]['name']);
        $response = self::createClient()->request('GET', 'filter_with_state_options?date[strictly_after]='.$d->format('Y-m-d'));
        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals('after', $a['hydra:member'][0]['name']);
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

        $foos = array_map(fn ($item) => $item['foo'], $filteredItems);
        sort($foos);
        sort($expectedFoos);

        $this->assertSame($expectedFoos, $foos, 'The "foo" values do not match the expected values.');
    }

    public static function partialFilterParameterProviderForSearchFilterParameter(): \Generator
    {
        // Fixtures Recap (from DoctrineTest::loadFixtures with SearchFilterParameter):
        // 3x foo = 'foo'
        // 2x foo = 'bar'
        // 1x foo = 'baz'

        yield 'partial match on foo (fo -> 3x foo)' => [
            '/search_filter_parameter?searchPartial[foo]=fo',
            3,
            ['foo', 'foo', 'foo'],
        ];

        yield 'partial match on foo (ba -> 2x bar, 1x baz)' => [
            '/search_filter_parameter?searchPartial[foo]=ba',
            3,
            ['bar', 'bar', 'baz'],
        ];

        yield 'partial match on foo (az -> 1x baz)' => [
            '/search_filter_parameter?searchPartial[foo]=az',
            1,
            ['baz'],
        ];
    }

    public function testQueryParameterWithPropertyArgument(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $resource = ProductWithQueryParameter::class;
        $this->recreateSchema([$resource]);
        $this->loadProductFixtures($resource);

        // Test search[:property] with 'title'
        $response = self::createClient()->request('GET', '/product_with_query_parameters?search[title]=Awesome');
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray()['hydra:member']);
        $this->assertEquals('Awesome Widget', $response->toArray()['hydra:member'][0]['title']);

        // Test search[:property] with 'description'
        $response = self::createClient()->request('GET', '/product_with_query_parameters?search[description]=super');
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray()['hydra:member']);
        $this->assertEquals('Super Gadget', $response->toArray()['hydra:member'][0]['title']);

        // Test filter[:property] with 'category'
        $response = self::createClient()->request('GET', '/product_with_query_parameters?filter[category]=Electronics');
        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $response->toArray()['hydra:member']);

        // Test filter[:property] with 'brand'
        $response = self::createClient()->request('GET', '/product_with_query_parameters?filter[brand]=BrandY');
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray()['hydra:member']);
        $this->assertEquals('Super Gadget', $response->toArray()['hydra:member'][0]['title']);

        // Test order[:property] with 'rating'
        $response = self::createClient()->request('GET', '/product_with_query_parameters?order[rating]=desc');
        $this->assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(3, $members);
        $this->assertEquals('Awesome Widget', $members[0]['title']);
        $this->assertEquals('Super Gadget', $members[1]['title']);
        $this->assertEquals('Mega Device', $members[2]['title']);
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

    private function loadProductFixtures(string $resourceClass): void
    {
        $container = static::$kernel->getContainer();
        $registry = $this->isMongoDB() ? $container->get('doctrine_mongodb') : $container->get('doctrine');
        $manager = $registry->getManager();

        $product1 = new $resourceClass();
        $product1->sku = 'SKU001';
        $product1->title = 'Awesome Widget';
        $product1->description = 'A really awesome widget.';
        $product1->category = 'Electronics';
        $product1->brand = 'BrandX';
        $product1->rating = 5;
        $product1->stock = 100;
        $product1->tags = ['new', 'sale'];
        $manager->persist($product1);

        $product2 = new $resourceClass();
        $product2->sku = 'SKU002';
        $product2->title = 'Super Gadget';
        $product2->description = 'A super cool gadget.';
        $product2->category = 'Electronics';
        $product2->brand = 'BrandY';
        $product2->rating = 4;
        $product2->stock = 50;
        $product2->tags = ['popular'];
        $manager->persist($product2);

        $product3 = new $resourceClass();
        $product3->sku = 'SKU003';
        $product3->title = 'Mega Device';
        $product3->description = 'A mega useful device.';
        $product3->category = 'Home';
        $product3->brand = 'BrandX';
        $product3->rating = 3;
        $product3->stock = 200;
        $product3->tags = ['clearance'];
        $manager->persist($product3);

        $manager->flush();
    }

    #[DataProvider('openApiParameterDocumentationProvider')]
    public function testOpenApiParameterDocumentation(string $parameterName, bool $shouldHaveArrayNotation, string $expectedStyle, bool $expectedExplode, string $expectedDescription = '', ?array $expectedSchema = null): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $resource = ProductWithQueryParameter::class;
        $this->recreateSchema([$resource]);

        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $openApiDoc = $response->toArray();

        $parameters = $openApiDoc['paths']['/product_with_query_parameters']['get']['parameters'];
        $foundParameter = null;
        $expectedName = $shouldHaveArrayNotation ? $parameterName.'[]' : $parameterName;
        $alternativeName = $shouldHaveArrayNotation ? $parameterName : $parameterName.'[]';

        foreach ($parameters as $parameter) {
            if ($parameter['name'] === $expectedName || $parameter['name'] === $alternativeName) {
                $foundParameter = $parameter;
                break;
            }
        }

        $this->assertNotNull($foundParameter, \sprintf('%s parameter should be present in OpenAPI documentation', $parameterName));
        $this->assertSame($expectedName, $foundParameter['name'], \sprintf('Parameter name should%s have [] suffix', $shouldHaveArrayNotation ? '' : ' NOT'));
        $this->assertSame('query', $foundParameter['in']);
        $this->assertFalse($foundParameter['required']);

        if (isset($foundParameter['expectedDescription'])) {
            $this->assertSame($expectedDescription, $foundParameter['description'] ?? '', \sprintf('Description should be %s', $expectedDescription));
        }

        if ($expectedSchema) {
            $this->assertSame($expectedSchema, $foundParameter['schema'], 'Parameter schema should match expected schema');
        }

        $this->assertSame($expectedStyle, $foundParameter['style'] ?? 'form', \sprintf('Style should be %s', $expectedStyle));
        $this->assertSame($expectedExplode, $foundParameter['explode'] ?? false, \sprintf('Explode should be %s', $expectedExplode ? 'true' : 'false'));
    }

    public static function openApiParameterDocumentationProvider(): array
    {
        return [
            'default behavior (no castToArray, no schema) should use array notation' => [
                'parameterName' => 'brand',
                'shouldHaveArrayNotation' => true,
                'expectedStyle' => 'deepObject',
                'expectedExplode' => true,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'default behavior with an extra description' => [
                'parameterName' => 'brandWithDescription',
                'shouldHaveArrayNotation' => true,
                'expectedStyle' => 'deepObject',
                'expectedExplode' => true,
                'expectedDescription' => 'Extra description about the filter',
                'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'explicit schema type string should not use array notation' => [
                'parameterName' => 'exactBrand',
                'shouldHaveArrayNotation' => false,
                'expectedStyle' => 'form',
                'expectedExplode' => false,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'string'],
            ],
            'castToArray false should not use array notation' => [
                'parameterName' => 'exactCategory',
                'shouldHaveArrayNotation' => false,
                'expectedStyle' => 'form',
                'expectedExplode' => false,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'string'],
            ],
            'with schema and default castToArray should wrap schema in array type' => [
                'parameterName' => 'tags',
                'shouldHaveArrayNotation' => true,
                'expectedStyle' => 'deepObject',
                'expectedExplode' => true,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'array', 'items' => ['anyOf' => [['type' => 'array', 'items' => ['type' => 'string']], ['type' => 'string']]]],
            ],
        ];
    }
}
