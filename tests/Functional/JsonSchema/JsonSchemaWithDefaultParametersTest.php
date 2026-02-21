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

use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests;
use ApiPlatform\Tests\Functional\DefaultParametersAppKernel;

/**
 * Test that JsonSchema can be generated when default parameters are configured.
 *
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
final class JsonSchemaWithDefaultParametersTest extends ApiTestCase
{
    protected SchemaFactoryInterface $schemaFactory;
    protected OperationMetadataFactoryInterface $operationMetadataFactory;

    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return DefaultParametersAppKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $this->operationMetadataFactory = self::getContainer()->get('api_platform.metadata.operation.metadata_factory');
    }

    public function testJsonSchemaCanBeGeneratedWithDefaultParameters(): void
    {
        $hasDefaultParameters = false;
        $resourceMetadata = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory')->create(BagOfTests::class);

        foreach ($resourceMetadata as $operation) {
            $parameters = $operation->getParameters() ?? [];
            foreach ($parameters as $parameter) {
                if ('X-API-Key' === $parameter->getKey()) {
                    $hasDefaultParameters = true;
                    $this->assertFalse($parameter->getRequired());
                    $this->assertSame('API key for authentication', $parameter->getDescription());
                    break;
                }
            }
            if ($hasDefaultParameters) {
                break;
            }
        }

        $this->assertTrue($hasDefaultParameters, 'Default parameter "X-API-Key" should be applied to resource operations');

        $schema = $this->schemaFactory->buildSchema(BagOfTests::class, 'jsonld');

        $this->assertInstanceOf(\ArrayObject::class, $schema);

        $this->assertArrayHasKey('definitions', $schema);
        $this->assertNotEmpty($schema['definitions']);

        foreach ($schema['definitions'] as $key => $definition) {
            $this->assertIsString($key);
            $this->assertNotNull($definition);
        }
    }
}
