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

namespace ApiPlatform\Tests\Functional\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Animal;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\AnimalObservation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6317\Issue6317;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Species;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class JsonApiJsonSchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected SchemaFactoryInterface $schemaFactory;
    protected OperationMetadataFactoryInterface $operationMetadataFactory;
    protected static ?bool $alwaysBootKernel = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $this->operationMetadataFactory = self::getContainer()->get('api_platform.metadata.operation.metadata_factory');
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            AnimalObservation::class,
            Animal::class,
            Species::class,
            Question::class,
            Answer::class,
            Issue6317::class,
        ];
    }

    public function testJsonApi(): void
    {
        $speciesSchema = $this->schemaFactory->buildSchema(Issue6317::class, 'jsonapi', Schema::TYPE_OUTPUT);
        $this->assertEquals('#/definitions/Issue6317.jsonapi', $speciesSchema['$ref']);
        $this->assertEquals([
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                        ],
                        'type' => [
                            'type' => 'string',
                        ],
                        'attributes' => [
                            '$ref' => '#/definitions/Issue6317',
                        ],
                    ],
                    'required' => [
                        'type',
                        'id',
                    ],
                ],
            ],
        ], $speciesSchema['definitions']['Issue6317.jsonapi']);
    }

    public function testJsonApiIncludesSchemaForQuestion(): void
    {
        if ('mongodb' === self::getContainer()->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $questionSchema = $this->schemaFactory->buildSchema(Question::class, 'jsonapi', Schema::TYPE_OUTPUT);
        $json = $questionSchema->getDefinitions();
        $properties = $json['Question.jsonapi']['properties']['data']['properties'];
        $included = $json['Question.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('answer', $properties['relationships']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertArrayHasKey('$ref', $included['items']['anyOf'][0]);
        $this->assertSame('#/definitions/Answer.jsonapi', $included['items']['anyOf'][0]['$ref']);
    }

    public function testJsonApiIncludesSchemaForAnimalObservation(): void
    {
        $animalObservationSchema = $this->schemaFactory->buildSchema(AnimalObservation::class, 'jsonapi', Schema::TYPE_OUTPUT);
        $json = $animalObservationSchema->getDefinitions();
        $properties = $json['AnimalObservation.jsonapi']['properties']['data']['properties'];
        $included = $json['AnimalObservation.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('individuals', $properties['relationships']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertSame('#/definitions/Animal.jsonapi', $included['items']['anyOf'][0]['$ref']);
    }

    public function testJsonApiIncludesSchemaForAnimal(): void
    {
        $animalSchema = $this->schemaFactory->buildSchema(Animal::class, 'jsonapi', Schema::TYPE_OUTPUT);
        $json = $animalSchema->getDefinitions();
        $properties = $json['Animal.jsonapi']['properties']['data']['properties'];
        $included = $json['Animal.jsonapi']['properties']['included'];

        $this->assertArrayHasKey('species', $properties['relationships']['properties']);
        $this->assertArrayHasKey('anyOf', $included['items']);
        $this->assertCount(1, $included['items']['anyOf']);
        $this->assertSame('#/definitions/Species.jsonapi', $included['items']['anyOf'][0]['$ref']);
    }

    public function testJsonApiIncludesSchemaForSpecies(): void
    {
        $speciesSchema = $this->schemaFactory->buildSchema(Species::class, 'jsonapi', Schema::TYPE_OUTPUT, forceCollection: true);
        $this->assertArraySubset(
            [
                'description' => 'Species.jsonapi collection.',
                'allOf' => [
                    ['$ref' => '#/definitions/JsonApiCollectionBaseSchema'],
                    [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => [
                                            'type' => 'string',
                                        ],
                                        'type' => [
                                            'type' => 'string',
                                        ],
                                        'attributes' => [
                                            '$ref' => '#/definitions/Species',
                                        ],
                                    ],
                                    'required' => [
                                        'type',
                                        'id',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $speciesSchema->getArrayCopy()
        );
    }
}
