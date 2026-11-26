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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Resource\Factory\PolymorphicGraphQlResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

final class PolymorphicGraphQlResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testDefaultOperationsAreReplacedByNestedOnes(): void
    {
        $factory = $this->createFactory([
            'item_query' => (new Query(name: 'item_query'))->withExtraProperties(['graphql_default_operation' => true]),
            'collection_query' => (new QueryCollection(name: 'collection_query'))->withExtraProperties(['graphql_default_operation' => true]),
        ], true);

        $operations = $factory->create(\stdClass::class)[0]->getGraphQlOperations();

        $this->assertSame(['item_query', 'collection_query'], array_keys($operations));
        $this->assertInstanceOf(QueryCollection::class, $operations['collection_query']);
        $this->assertInstanceOf(Query::class, $operations['item_query']);
        $this->assertTrue($operations['item_query']->getNested());
        $this->assertTrue($operations['collection_query']->getNested());
        $this->assertArrayNotHasKey('graphql_default_operation', $operations['item_query']->getExtraProperties());
    }

    public function testNestedOperationsReceiveTheGlobalDefaults(): void
    {
        $factory = $this->createFactory(
            ['item_query' => (new Query(name: 'item_query'))->withExtraProperties(['graphql_default_operation' => true])],
            true,
            ['normalization_context' => ['skip_null_values' => true]]
        );

        $operations = $factory->create(\stdClass::class)[0]->getGraphQlOperations();

        $this->assertSame(['skip_null_values' => true], $operations['collection_query']->getNormalizationContext());
    }

    public function testExplicitEmptyOperationsAreUntouched(): void
    {
        $operations = ['item_query' => (new Query(name: 'item_query'))->withNested(true)];
        $factory = $this->createFactory($operations, true);

        $this->assertSame($operations, $factory->create(\stdClass::class)[0]->getGraphQlOperations());
    }

    public function testSubtypeResourcesAreDetectedThroughTheirParentMap(): void
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection(\UnexpectedValueException::class, [(new ApiResource(class: \UnexpectedValueException::class))->withGraphQlOperations(['item_query' => new Query(name: 'item_query')])]));

        $resolver = $this->createStub(ClassDiscriminatorResolverInterface::class);
        $resolver->method('getMappingForClass')->willReturnCallback(static fn (string $class): ?ClassDiscriminatorMapping => \RuntimeException::class === $class ? new ClassDiscriminatorMapping('kind', ['iterator' => \UnexpectedValueException::class]) : null);

        $this->expectException(RuntimeException::class);

        (new PolymorphicGraphQlResourceMetadataCollectionFactory($resolver, $decorated))->create(\UnexpectedValueException::class);
    }

    public function testExplicitOperationsThrow(): void
    {
        $factory = $this->createFactory(['item_query' => new Query(name: 'item_query')], true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('GraphQL is not supported yet for "%s", a resource with a discriminator map', \stdClass::class));

        $factory->create(\stdClass::class);
    }

    public function testNonPolymorphicResourcesAreUntouched(): void
    {
        $operations = ['item_query' => new Query(name: 'item_query')];
        $factory = $this->createFactory($operations, false);

        $this->assertSame($operations, $factory->create(\stdClass::class)[0]->getGraphQlOperations());
    }

    /**
     * @param array<string, Query> $graphQlOperations
     */
    private function createFactory(array $graphQlOperations, bool $hasMapping, array $defaults = []): PolymorphicGraphQlResourceMetadataCollectionFactory
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [(new ApiResource(class: \stdClass::class))->withGraphQlOperations($graphQlOperations)]));

        $resolver = $this->createStub(ClassDiscriminatorResolverInterface::class);
        $resolver->method('getMappingForClass')->willReturn($hasMapping ? new ClassDiscriminatorMapping('kind', ['standard' => \stdClass::class]) : null);

        return new PolymorphicGraphQlResourceMetadataCollectionFactory($resolver, $decorated, $defaults);
    }
}
