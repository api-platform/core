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

namespace ApiPlatform\JsonApi\Tests\JsonSchema;

use ApiPlatform\JsonApi\JsonSchema\SchemaFactory;
use ApiPlatform\JsonApi\Tests\Fixtures\Order;
use ApiPlatform\JsonApi\Tests\Fixtures\OrderInput;
use ApiPlatform\JsonApi\Tests\Fixtures\OrderLineInput;
use ApiPlatform\JsonApi\Tests\Fixtures\RelatedDummy;
use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

class InputDtoSchemaFactoryTest extends TestCase
{
    public function testInputSchemaDocumentsEmbeddedDtoCollectionAsAttribute(): void
    {
        $operation = (new Post(input: ['class' => OrderInput::class]))->withName('post')->withShortName('Order');
        $schemaFactory = $this->buildSchemaFactory($operation);

        $schema = $schemaFactory->buildSchema(Order::class, 'jsonapi', Schema::TYPE_INPUT, $operation);
        $this->assertSame(['$ref' => '#/definitions/'.$schema->getRootDefinitionKey().'.resource'], $schema->getDefinitions()[$schema->getRootDefinitionKey()]['properties']['data']);
        $data = $schema->getDefinitions()[$schema->getRootDefinitionKey().'.resource'];

        $this->assertArrayNotHasKey('relationships', $data['properties']);
        $this->assertEqualsCanonicalizing(['reference', 'lines'], array_keys($data['properties']['attributes']['properties']));
    }

    public function testOutputSchemaKeepsResourceCollectionAsRelationship(): void
    {
        $operation = (new Get())->withName('get')->withShortName('Order');
        $schemaFactory = $this->buildSchemaFactory($operation);

        $schema = $schemaFactory->buildSchema(Order::class, 'jsonapi', Schema::TYPE_OUTPUT, $operation);
        $this->assertSame(['$ref' => '#/definitions/'.$schema->getRootDefinitionKey().'.resource'], $schema->getDefinitions()[$schema->getRootDefinitionKey()]['properties']['data']);
        $data = $schema->getDefinitions()[$schema->getRootDefinitionKey().'.resource'];

        $this->assertArrayHasKey('lines', $data['properties']['relationships']['properties']);
        $this->assertArrayNotHasKey('lines', $data['properties']['attributes']['properties']);
    }

    private function buildSchemaFactory(Get|Post $operation): SchemaFactory
    {
        $resourceMetadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->method('create')->willReturnCallback(static fn (string $class): ResourceMetadataCollection => new ResourceMetadataCollection($class, [
            (new ApiResource())->withOperations(new Operations(Order::class === $class
                ? [$operation->getName() => $operation]
                : ['get' => (new Get())->withName('get')->withShortName('RelatedDummy')]
            )),
        ]));

        $propertyNameCollectionFactory = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')->willReturnCallback(static fn (string $class): PropertyNameCollection => new PropertyNameCollection(match ($class) {
            Order::class, OrderInput::class => ['reference', 'lines'],
            OrderLineInput::class => ['sku'],
            default => [],
        }));

        $types = [
            Order::class.'::reference' => Type::string(),
            Order::class.'::lines' => Type::list(Type::object(RelatedDummy::class)),
            OrderInput::class.'::reference' => Type::string(),
            OrderInput::class.'::lines' => Type::list(Type::object(OrderLineInput::class)),
            OrderLineInput::class.'::sku' => Type::string(),
        ];
        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturnCallback(static fn (string $class, string $property): ApiProperty => (new ApiProperty())->withNativeType($types[$class.'::'.$property])->withReadable(true)->withWritable(true));

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturnCallback(static fn (string $class): bool => \in_array($class, [Order::class, RelatedDummy::class], true));

        $definitionNameFactory = new DefinitionNameFactory();

        $baseSchemaFactory = new BaseSchemaFactory(
            resourceMetadataFactory: $resourceMetadataFactory,
            propertyNameCollectionFactory: $propertyNameCollectionFactory,
            propertyMetadataFactory: $propertyMetadataFactory,
            resourceClassResolver: $resourceClassResolver,
            definitionNameFactory: $definitionNameFactory,
        );

        return new SchemaFactory(
            schemaFactory: $baseSchemaFactory,
            propertyMetadataFactory: $propertyMetadataFactory,
            resourceClassResolver: $resourceClassResolver,
            resourceMetadataFactory: $resourceMetadataFactory,
            definitionNameFactory: $definitionNameFactory,
        );
    }
}
