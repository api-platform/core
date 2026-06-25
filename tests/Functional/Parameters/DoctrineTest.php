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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\FilterWithStateOptionsAndNoApiFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterWithStateOptionsAndNoApiFilterEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterWithStateOptionsEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ProductWithQueryParameter;
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
        return [
            FilterWithStateOptions::class,
            FilterWithStateOptionsAndNoApiFilter::class,
            ProductWithQueryParameter::class,
        ];
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

    public function testStateOptionsAndNoApiFilter(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->recreateSchema([FilterWithStateOptionsAndNoApiFilterEntity::class]);

        $manager = $container->get('doctrine')->getManager();
        $manager->persist(new FilterWithStateOptionsAndNoApiFilterEntity(name: 'current'));
        $manager->persist(new FilterWithStateOptionsAndNoApiFilterEntity(name: 'null'));
        $manager->persist(new FilterWithStateOptionsAndNoApiFilterEntity(name: 'after'));
        $manager->flush();

        $uri = '/filter_with_state_options_and_no_api_filters_api_resource';

        $response = self::createClient()->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        $this->assertSame('hydra:Collection', $a['@type']);
        $this->assertSame(3, $a['hydra:totalItems']);
        $this->assertCount(3, $a['hydra:member']);

        $response = self::createClient()->request('GET', $uri.'?search[name]=aft');
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        $this->assertSame('hydra:Collection', $a['@type']);
        $this->assertSame(1, $a['hydra:totalItems']);
        $this->assertCount(1, $a['hydra:member']);
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
    public function testOpenApiParameterDocumentation(string $parameterName, bool $shouldHaveArrayNotation, string $expectedStyle, bool $expectedExplode, string $expectedDescription = '', ?array $expectedSchema = null, bool $shouldHaveBothVariants = false): void
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

        if ($shouldHaveBothVariants) {
            $singularParameter = null;
            $arrayParameter = null;

            foreach ($parameters as $parameter) {
                if ($parameter['name'] === $parameterName) {
                    $singularParameter = $parameter;
                }
                if ($parameter['name'] === $parameterName.'[]') {
                    $arrayParameter = $parameter;
                }
            }

            $this->assertNotNull($singularParameter, \sprintf('%s singular parameter should be present in OpenAPI documentation', $parameterName));
            $this->assertNotNull($arrayParameter, \sprintf('%s[] array parameter should be present in OpenAPI documentation', $parameterName));
            $this->assertSame('query', $arrayParameter['in']);
            $this->assertSame($expectedStyle, $arrayParameter['style'] ?? 'form');
            $this->assertSame($expectedExplode, $arrayParameter['explode'] ?? false);

            if ($expectedSchema) {
                $this->assertSame($expectedSchema, $arrayParameter['schema'], 'Array parameter schema should match expected schema');
            }

            return;
        }

        $foundParameter = null;
        $expectedName = $shouldHaveArrayNotation ? $parameterName.'[]' : $parameterName;

        foreach ($parameters as $parameter) {
            if ($parameter['name'] === $expectedName) {
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
            'default behavior (no castToArray, no schema) should generate both singular and array parameters' => [
                'parameterName' => 'brand',
                'shouldHaveArrayNotation' => true,
                'expectedStyle' => 'deepObject',
                'expectedExplode' => true,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string']],
                'shouldHaveBothVariants' => true,
            ],
            'default behavior with an extra description should generate both variants' => [
                'parameterName' => 'brandWithDescription',
                'shouldHaveArrayNotation' => true,
                'expectedStyle' => 'deepObject',
                'expectedExplode' => true,
                'expectedDescription' => 'Extra description about the filter',
                'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string']],
                'shouldHaveBothVariants' => true,
            ],
            'explicit schema type string should not use array notation' => [
                'parameterName' => 'exactBrand',
                'shouldHaveArrayNotation' => false,
                'expectedStyle' => 'form',
                'expectedExplode' => true,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'string'],
            ],
            'castToArray false should not use array notation' => [
                'parameterName' => 'exactCategory',
                'shouldHaveArrayNotation' => false,
                'expectedStyle' => 'form',
                'expectedExplode' => true,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'string'],
            ],
            'with schema and default castToArray should generate both variants wrapping schema in array type' => [
                'parameterName' => 'tags',
                'shouldHaveArrayNotation' => true,
                'expectedStyle' => 'deepObject',
                'expectedExplode' => true,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'array', 'items' => ['anyOf' => [['type' => 'array', 'items' => ['type' => 'string']], ['type' => 'string']]]],
                'shouldHaveBothVariants' => true,
            ],
            'with schema and default castToArray should generate both variants without wrapping if already array' => [
                'parameterName' => 'listOfTags',
                'shouldHaveArrayNotation' => true,
                'expectedStyle' => 'deepObject',
                'expectedExplode' => true,
                'expectedDescription' => '',
                'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string']],
                'shouldHaveBothVariants' => true,
            ],
        ];
    }
}
