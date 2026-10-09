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
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain\GenIdChainInverseMiddle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain\GenIdChainInverseRoot;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain\GenIdChainLeaf;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain\GenIdChainMiddle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain\GenIdChainNullMiddle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain\GenIdChainNullRoot;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGenIdChain\GenIdChainRoot;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class JsonSchemaGenIdChainTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected SchemaFactoryInterface $schemaFactory;

    protected static ?bool $alwaysBootKernel = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            GenIdChainRoot::class,
            GenIdChainMiddle::class,
            GenIdChainLeaf::class,
            GenIdChainInverseRoot::class,
            GenIdChainInverseMiddle::class,
            GenIdChainNullRoot::class,
            GenIdChainNullMiddle::class,
        ];
    }

    public function testExplicitTrueGenIdBelowFalseRelationKeepsId(): void
    {
        [$name, $definition] = $this->findLeafDefinition(GenIdChainRoot::class);

        $this->assertStringEndsNotWith('_noid', $name);
        $this->assertSame('#/definitions/HydraItemBaseSchema', $definition['allOf'][0]['$ref']);
    }

    public function testFalseGenIdBelowTrueRelationDropsId(): void
    {
        [$name, $definition] = $this->findLeafDefinition(GenIdChainInverseRoot::class);

        $this->assertStringEndsWith('_noid', $name);
        $this->assertSame('#/definitions/HydraItemBaseSchemaWithoutId', $definition['allOf'][0]['$ref']);
    }

    public function testNullGenIdInheritsFromParent(): void
    {
        [$name, $definition] = $this->findLeafDefinition(GenIdChainNullRoot::class);

        $this->assertStringEndsWith('_noid', $name);
        $this->assertSame('#/definitions/HydraItemBaseSchemaWithoutId', $definition['allOf'][0]['$ref']);
    }

    /**
     * @return array{string, array<string, mixed>}
     */
    private function findLeafDefinition(string $class): array
    {
        $schema = $this->schemaFactory->buildSchema($class, 'jsonld');
        $definitions = $schema->getDefinitions();

        $names = array_filter(array_keys($definitions->getArrayCopy()), static fn (string $name): bool => str_starts_with($name, 'GenIdChainLeaf'));
        $this->assertCount(1, $names, json_encode(array_keys($definitions->getArrayCopy())));
        $name = reset($names);

        return [$name, $definitions[$name]->getArrayCopy()];
    }
}
