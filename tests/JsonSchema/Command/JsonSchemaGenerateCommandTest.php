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

namespace ApiPlatform\Tests\JsonSchema\Command;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Animal;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\AnimalObservation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BackedEnumIntegerResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BackedEnumStringResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5501\BrokenDocs;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6299\Issue6299;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6317\Issue6317;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6800\TestApiDocHashmapArrayObjectIssue;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ResourceWithEnumProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Species;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\Issue5998Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\ProductCode;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\SaveProduct;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6212\Nest;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Jacques Lefebvre <jacques@les-tilleuls.coop>
 */
class JsonSchemaGenerateCommandTest extends KernelTestCase
{
    use SetupClassResourcesTrait;
    private ApplicationTester $tester;
    private string $entityClass;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(true);
        $application->setAutoExit(false);

        $this->entityClass = 'mongodb' === $kernel->getEnvironment() ? DocumentDummy::class : Dummy::class;
        $this->tester = new ApplicationTester($application);
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Dummy::class,
            BrokenDocs::class,
            Nest::class,
            BagOfTests::class,
            ResourceWithEnumProperty::class,
            Issue6299::class,
            RelatedDummy::class,
            Question::class,
            Answer::class,
            AnimalObservation::class,
            Animal::class,
            Species::class,
            Issue6317::class,
            ProductCode::class,
            Issue5998Product::class,
            BackedEnumIntegerResource::class,
            BackedEnumStringResource::class,
        ];
    }

    public function testExecuteWithoutOption(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass]);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithItemOperationGet(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies/{id}{._format}_get', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithCollectionOperationGet(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_get_collection', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithJsonldFormatOption(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_post', '--format' => 'jsonld', '--type' => 'output']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('@id', $result);
        $this->assertStringContainsString('@context', $result);
        $this->assertStringContainsString('@type', $result);
    }

    public function testExecuteWithJsonldTypeInput(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_post', '--format' => 'jsonld', '--type' => 'input']);
        $result = $this->tester->getDisplay();

        $this->assertStringNotContainsString('@id', $result);
        $this->assertStringNotContainsString('@context', $result);
        $this->assertStringNotContainsString('@type', $result);
    }

    /**
     * Test issue #5501, the locations relation inside BrokenDocs is a Resource (named Related) but its only operation is a NotExposed.
     * Still, serializer groups are set, and therefore it is a "readableLink" so we actually want to compute the schema, even if it's not accessible
     * directly, it is accessible through that relation.
     */
    public function testExecuteWithNotExposedResourceAndReadableLink(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => BrokenDocs::class, '--type' => 'output']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('Related-location.read_collection', $result);
    }

    /**
     * When serializer groups are present the Schema should have an embed resource. #5470 breaks array references when serializer groups are present.
     */
    #[\PHPUnit\Framework\Attributes\Group('orm')]
    public function testArraySchemaWithReference(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => BagOfTests::class, '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['tests'], [
            'type' => 'string',
            'foo' => 'bar',
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['nonResourceTests'], [
            'type' => 'array',
            'items' => [
                '$ref' => '#/definitions/NonResourceTestEntity.jsonld-write',
            ],
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['description'], [
            'maxLength' => 255,
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['type'], [
            '$ref' => '#/definitions/TestEntity.jsonld-write',
        ]);
    }

    public function testArraySchemaWithMultipleUnionTypesJsonLd(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Nest::class, '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['Nest']['properties']['owner']['anyOf'], [
            ['$ref' => '#/definitions/Wren'],
            ['$ref' => '#/definitions/Robin'],
            ['type' => 'null'],
        ]);

        $this->assertArrayHasKey('Wren', $json['definitions']);
        $this->assertArrayHasKey('Robin', $json['definitions']);
    }

    // public function testArraySchemaWithMultipleUnionTypesJsonApi(): void
    // {
    //     $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Nest::class, '--type' => 'output', '--format' => 'jsonapi']);
    //     $result = $this->tester->getDisplay();
    //     $json = json_decode($result, associative: true);
    //     $this->assertEquals($json['definitions']['Nest.jsonapi']['properties']['data']['properties']['attributes']['properties']['owner']['anyOf'], [
    //         ['$ref' => '#/definitions/Wren'],
    //         ['$ref' => '#/definitions/Robin'],
    //         ['type' => 'null'],
    //     ]);
    //
    //     $this->assertArrayHasKey('Wren', $json['definitions']);
    //     $this->assertArrayHasKey('Robin', $json['definitions']);
    // }
    //
    // public function testArraySchemaWithMultipleUnionTypesJsonHal(): void
    // {
    //     $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Nest::class, '--type' => 'output', '--format' => 'jsonhal']);
    //     $result = $this->tester->getDisplay();
    //     $json = json_decode($result, associative: true);
    //
    //     $this->assertEquals($json['definitions']['Nest.jsonhal']['properties']['owner']['anyOf'], [
    //         ['$ref' => '#/definitions/Wren.jsonhal'],
    //         ['$ref' => '#/definitions/Robin.jsonhal'],
    //         ['type' => 'null'],
    //     ]);
    //
    //     $this->assertArrayHasKey('Wren.jsonhal', $json['definitions']);
    //     $this->assertArrayHasKey('Robin.jsonhal', $json['definitions']);
    // }

    /**
     * Test issue #5998.
     */
    public function testWritableNonResourceRef(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => SaveProduct::class, '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['SaveProduct.jsonld']['properties']['codes']['items']['$ref'], '#/definitions/ProductCode.jsonld');
    }

    // /**
    //  * Test issue #6299.
    //  */
    // public function testOpenApiResourceRefIsNotOverwritten(): void
    // {
    //     $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Issue6299::class, '--type' => 'output']);
    //     $result = $this->tester->getDisplay();
    //     $json = json_decode($result, associative: true);
    //
    //     $this->assertEquals('#/definitions/DummyFriend', $json['definitions']['Issue6299.Issue6299OutputDto.jsonld']['properties']['itemDto']['$ref']);
    //     $this->assertEquals('#/definitions/DummyDate', $json['definitions']['Issue6299.Issue6299OutputDto.jsonld']['properties']['collectionDto']['items']['$ref']);
    // }

    /**
     * Test related Schema keeps json-ld context.
     */
    #[\PHPUnit\Framework\Attributes\Group('orm')]
    public function testSubSchemaJsonLd(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => RelatedDummy::class, '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertArrayHasKey('@id', $json['definitions']['ThirdLevel.jsonld-friends']['properties']);
    }

    #[\PHPUnit\Framework\Attributes\Group('orm')]
    public function testJsonApiIncludesSchema(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Question::class, '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Question.jsonapi']['properties']['data']['properties'];
        $included = $json['definitions']['Question.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('answer', $properties['relationships']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertArrayHasKey('$ref', $included['items']['anyOf'][0]);
        $this->assertSame('#/definitions/Answer.jsonapi', $included['items']['anyOf'][0]['$ref']);

        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => AnimalObservation::class, '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['AnimalObservation.jsonapi']['properties']['data']['properties'];
        $included = $json['definitions']['AnimalObservation.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('individuals', $properties['relationships']['properties']);
        $this->assertArrayNotHasKey('individuals', $properties['attributes']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertSame('#/definitions/Animal.jsonapi', $included['items']['anyOf'][0]['$ref']);

        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Animal::class, '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Animal.jsonapi']['properties']['data']['properties'];
        $included = $json['definitions']['Animal.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('species', $properties['relationships']['properties']);
        $this->assertArrayNotHasKey('species', $properties['attributes']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertSame('#/definitions/Species.jsonapi', $included['items']['anyOf'][0]['$ref']);

        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Species::class, '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Species.jsonapi']['properties']['data']['properties'];

        $this->assertArrayHasKey('kingdom', $properties['attributes']['properties']);
        $this->assertArrayHasKey('phylum', $properties['attributes']['properties']);
    }

    /**
     * Test issue #6317.
     */
    public function testBackedEnumExamplesAreNotLost(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Issue6317::class, '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Issue6317.jsonld']['properties'];

        $this->assertArrayHasKey('example', $properties['id']);
        $this->assertArrayHasKey('example', $properties['name']);
        // jsonldContext
        $this->assertArrayNotHasKey('example', $properties['ordinal']);
        // openapiContext
        $this->assertArrayNotHasKey('example', $properties['cardinal']);
    }

    public function testResourceWithEnumPropertiesSchema(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => ResourceWithEnumProperty::class, '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['ResourceWithEnumProperty.jsonld']['properties'];

        $this->assertSame(
            [
                'type' => ['string', 'null'],
                'format' => 'iri-reference',
                'example' => 'https://example.com/',
            ],
            $properties['intEnum']
        );
        $this->assertSame(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'format' => 'iri-reference',
                    'example' => 'https://example.com/',
                ],
            ],
            $properties['stringEnum']
        );
        $this->assertSame(
            [
                'type' => ['string', 'null'],
                'enum' => ['male', 'female', null],
            ],
            $properties['gender']
        );
        $this->assertSame(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'enum' => ['male', 'female'],
                ],
            ],
            $properties['genders']
        );
    }

    /**
     * Test feature #6716.
     */
    public function testGenId(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\DisableIdGeneration', '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $this->assertArrayNotHasKey('@id', $json['definitions']['DisableIdGenerationItem.jsonld']['properties']);
    }

    #[DataProvider('arrayPropertyTypeSyntaxProvider')]
    public function testOpenApiSchemaGenerationForArrayProperty(string $propertyName, array $expectedProperties): void
    {
        $this->tester->run([
            'command' => 'api:json-schema:generate',
            'resource' => TestApiDocHashmapArrayObjectIssue::class,
            '--operation' => '_api_/test_api_doc_hashmap_array_object_issues{._format}_get',
            '--type' => 'output',
            '--format' => 'jsonld',
        ]);

        $result = $this->tester->getDisplay();
        $json = json_decode($result, true);
        $definitions = $json['definitions'];
        $ressourceDefinitions = $definitions['TestApiDocHashmapArrayObjectIssue.jsonld'];

        $this->assertArrayHasKey('TestApiDocHashmapArrayObjectIssue.jsonld', $definitions);
        $this->assertEquals('object', $ressourceDefinitions['type']);
        $this->assertEquals($expectedProperties, $ressourceDefinitions['properties'][$propertyName]);
    }

    public static function arrayPropertyTypeSyntaxProvider(): \Generator
    {
        yield 'Array of Foo objects using array<Foo> syntax' => [
            'foos',
            [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/definitions/Foo.jsonld',
                ],
            ],
        ];
        yield 'Array of Foo objects using Foo[] syntax' => [
            'fooOtherSyntax',
            [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/definitions/Foo.jsonld',
                ],
            ],
        ];
        yield 'Hashmap of Foo objects using array<string, Foo> syntax' => [
            'fooHashmaps',
            [
                'type' => 'object',
                'additionalProperties' => [
                    '$ref' => '#/definitions/Foo.jsonld',
                ],
            ],
        ];
    }
}
