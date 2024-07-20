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

use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Jacques Lefebvre <jacques@les-tilleuls.coop>
 */
class JsonSchemaGenerateCommandTest extends KernelTestCase
{
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

        $this->assertStringContainsString('@id', $result);
        $this->assertStringContainsString('@context', $result);
        $this->assertStringContainsString('@type', $result);
    }

    /**
     * Test issue #5501, the locations relation inside BrokenDocs is a Resource (named Related) but its only operation is a NotExposed.
     * Still, serializer groups are set, and therefore it is a "readableLink" so we actually want to compute the schema, even if it's not accessible
     * directly, it is accessible through that relation.
     */
    public function testExecuteWithNotExposedResourceAndReadableLink(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5501\BrokenDocs', '--type' => 'output']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('Related.jsonld-location.read_collection', $result);
    }

    /**
     * When serializer groups are present the Schema should have an embed resource. #5470 breaks array references when serializer groups are present.
     */
    public function testArraySchemaWithReference(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests', '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write.input']['properties']['tests'], [
            'type' => 'string',
            'foo' => 'bar',
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write.input']['properties']['nonResourceTests'], [
            'type' => 'array',
            'items' => [
                '$ref' => '#/definitions/NonResourceTestEntity.jsonld-write.input',
            ],
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write.input']['properties']['description'], [
            'maxLength' => 255,
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write.input']['properties']['type'], [
            '$ref' => '#/definitions/TestEntity.jsonld-write.input',
        ]);
    }

    public function testArraySchemaWithMultipleUnionTypesJsonLd(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6212\Nest', '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['Nest.jsonld.output']['properties']['owner']['anyOf'], [
            ['$ref' => '#/definitions/Wren.jsonld.output'],
            ['$ref' => '#/definitions/Robin.jsonld.output'],
            ['type' => 'null'],
        ]);

        $this->assertArrayHasKey('Wren.jsonld.output', $json['definitions']);
        $this->assertArrayHasKey('Robin.jsonld.output', $json['definitions']);
    }

    public function testArraySchemaWithMultipleUnionTypesJsonApi(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6212\Nest', '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['Nest.jsonapi']['properties']['data']['properties']['attributes']['properties']['owner']['anyOf'], [
            ['$ref' => '#/definitions/Wren.jsonapi'],
            ['$ref' => '#/definitions/Robin.jsonapi'],
            ['type' => 'null'],
        ]);

        $this->assertArrayHasKey('Wren.jsonapi', $json['definitions']);
        $this->assertArrayHasKey('Robin.jsonapi', $json['definitions']);
    }

    public function testArraySchemaWithMultipleUnionTypesJsonHal(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6212\Nest', '--type' => 'output', '--format' => 'jsonhal']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['Nest.jsonhal']['properties']['owner']['anyOf'], [
            ['$ref' => '#/definitions/Wren.jsonhal'],
            ['$ref' => '#/definitions/Robin.jsonhal'],
            ['type' => 'null'],
        ]);

        $this->assertArrayHasKey('Wren.jsonhal', $json['definitions']);
        $this->assertArrayHasKey('Robin.jsonhal', $json['definitions']);
    }

    /**
     * TODO: add deprecation (TypeFactory will be deprecated in api platform 3.3).
     *
     * @group legacy
     */
    public function testArraySchemaWithTypeFactory(): void
    {
        $container = static::getContainer();

        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5896\Foo', '--type' => 'output']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['Foo.jsonld.output']['properties']['expiration'], ['type' => 'string', 'format' => 'date']);
    }

    /**
     * Test issue #5998.
     */
    public function testWritableNonResourceRef(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\SaveProduct', '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['SaveProduct.jsonld.input']['properties']['codes']['items']['$ref'], '#/definitions/ProductCode.jsonld.input');
    }

    /**
     * Test issue #6299.
     */
    public function testOpenApiResourceRefIsNotOverwritten(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6299\Issue6299', '--type' => 'output']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals('#/definitions/DummyFriend', $json['definitions']['Issue6299.Issue6299OutputDto.jsonld.output']['properties']['itemDto']['$ref']);
        $this->assertEquals('#/definitions/DummyDate', $json['definitions']['Issue6299.Issue6299OutputDto.jsonld.output']['properties']['collectionDto']['items']['$ref']);
    }

    /**
     * Test related Schema keeps json-ld context.
     */
    public function testSubSchemaJsonLd(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy', '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertArrayHasKey('@id', $json['definitions']['ThirdLevel.jsonld-friends.output']['properties']);
    }

    public function testJsonApiIncludesSchema(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question', '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Question.jsonapi']['properties']['data']['properties'];
        $included = $json['definitions']['Question.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('answer', $properties['relationships']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertArrayHasKey('$ref', $included['items']['anyOf'][0]);
        $this->assertSame('#/definitions/Answer.jsonapi', $included['items']['anyOf'][0]['$ref']);

        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\AnimalObservation', '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['AnimalObservation.jsonapi']['properties']['data']['properties'];
        $included = $json['definitions']['AnimalObservation.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('individuals', $properties['relationships']['properties']);
        $this->assertArrayNotHasKey('individuals', $properties['attributes']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertSame('#/definitions/Animal.jsonapi', $included['items']['anyOf'][0]['$ref']);

        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Animal', '--type' => 'output', '--format' => 'jsonapi']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Animal.jsonapi']['properties']['data']['properties'];
        $included = $json['definitions']['Animal.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('species', $properties['relationships']['properties']);
        $this->assertArrayNotHasKey('species', $properties['attributes']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertSame('#/definitions/Species.jsonapi', $included['items']['anyOf'][0]['$ref']);

        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Species', '--type' => 'output', '--format' => 'jsonapi']);
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
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6317\Issue6317', '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Issue6317.jsonld.output']['properties'];

        $this->assertArrayHasKey('example', $properties['id']);
        $this->assertArrayHasKey('example', $properties['name']);
        // jsonldContext
        $this->assertArrayNotHasKey('example', $properties['ordinal']);
        // openapiContext
        $this->assertArrayNotHasKey('example', $properties['cardinal']);
    }

    public function testResourceWithEnumPropertiesSchema(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ResourceWithEnumProperty', '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['ResourceWithEnumProperty.jsonld.output']['properties'];

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
}
