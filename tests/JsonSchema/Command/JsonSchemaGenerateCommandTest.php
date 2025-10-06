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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6299\Issue6299;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6317\Issue6317;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6800\TestApiDocHashmapArrayObjectIssue;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ResourceWithEnumProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\Issue5998Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\ProductCode;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\SaveProduct;
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
            BagOfTests::class,
            ResourceWithEnumProperty::class,
            Issue6299::class,
            RelatedDummy::class,
            Question::class,
            Answer::class,
            AnimalObservation::class,
            Animal::class,
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

    public function testExecuteWithJsonMergePatchTypeInput(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies/{id}{._format}_patch', '--format' => 'json', '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertArrayNotHasKey('Dummy', $json['definitions']);
        $this->assertArrayHasKey('Dummy.jsonMergePatch', $json['definitions']);
        $this->assertArrayNotHasKey('required', $json['definitions']['Dummy.jsonMergePatch']);
    }

    /**
     * Test issue #5998.
     */
    public function testWritableNonResourceRef(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => SaveProduct::class, '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['SaveProduct']['properties']['codes']['items']['$ref'], '#/definitions/ProductCode');
    }

    /**
     * Test issue #6299.
     */
    public function testOpenApiResourceRefIsNotOverwritten(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Issue6299::class, '--type' => 'output']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals('#/definitions/DummyFriend', $json['definitions']['Issue6299.Issue6299OutputDto.jsonld']['allOf'][1]['properties']['itemDto']['$ref']);
        $this->assertEquals('#/definitions/DummyDate', $json['definitions']['Issue6299.Issue6299OutputDto.jsonld']['allOf'][1]['properties']['collectionDto']['items']['$ref']);
    }

    /**
     * Test issue #6317.
     */
    public function testBackedEnumExamplesAreNotLost(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => Issue6317::class, '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $properties = $json['definitions']['Issue6317.jsonld']['allOf'][1]['properties'];
        $this->assertArrayHasKey('example', $properties['id']);
        $this->assertArrayHasKey('example', $properties['name']);
        $this->assertArrayNotHasKey('example', $properties['ordinal']);
        $this->assertArrayNotHasKey('example', $properties['cardinal']);
    }

    /**
     * Test feature #6716.
     * Note: in this test the embed object is not a resource, the behavior is different from where the embeded is an ApiResource.
     */
    public function testGenId(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\DisableIdGeneration', '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);
        $this->assertArrayNotHasKey('@id', $json['definitions']['DisableIdGenerationItem.jsonld_noid']['properties']);
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

        $this->assertArrayHasKey('TestApiDocHashmapArrayObjectIssue.jsonld', $definitions);

        $ressourceDefinitions = $definitions['TestApiDocHashmapArrayObjectIssue.jsonld']['allOf'][1];

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
