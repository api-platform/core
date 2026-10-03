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

namespace ApiPlatform\Tests\JsonLd\Serializer;

use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\JsonLd\Serializer\ItemNormalizer;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HydraOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalize(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())
                ->withShortName('Dummy')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('Dummy')])),
        ]));
        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->willReturn($propertyNameCollection);

        $propertyMetadata = (new ApiProperty())->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::type('array'))->willReturn($propertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, UrlGeneratorInterface::ABS_PATH, null, Argument::any())->willReturn('/dummies/1988');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilderProphecy->getResourceContextUri(Dummy::class)->willReturn('/contexts/Dummy');

        $normalizer = new ItemNormalizer(
            $resourceMetadataCollectionFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $contextBuilderProphecy->reveal(),
            null,
            null,
            null,
            []
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = [
            '@context' => '/contexts/Dummy',
            '@id' => '/dummies/1988',
            '@type' => 'Dummy',
            'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy));
    }

    public function testNormalizeExposesTheReferencedHydraOperationsGrantedBySecurity(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $accessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $accessCheckerProphecy->isGranted(Dummy::class, "is_granted('ROLE_ADMIN')", Argument::withEntry('object', $dummy))->willReturn(true)->shouldBeCalledOnce();

        $normalizer = $this->createHydraOperationsNormalizer($dummy, [
            new Get(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'get', hydraOperations: [new HydraOperation(method: 'DELETE', uriTemplate: '/dummies/{id}{._format}', name: 'delete')]),
            new Delete(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'delete', security: "is_granted('ROLE_ADMIN')", hideHydraOperation: true),
        ], [], $accessCheckerProphecy->reveal());

        $this->assertEquals([
            '@context' => '/contexts/Dummy',
            '@id' => '/dummies/1',
            '@type' => 'Dummy',
            'operation' => [
                [
                    '@type' => ['Operation', 'schema:DeleteAction'],
                    'description' => 'Deletes the Dummy resource.',
                    'method' => 'DELETE',
                    'returns' => 'owl:Nothing',
                    'title' => 'deleteDummy',
                ],
            ],
            'name' => 'hello',
        ], $normalizer->normalize($dummy));
    }

    public function testNormalizeHidesTheHydraOperationsDeniedBySecurity(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $accessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $accessCheckerProphecy->isGranted(Dummy::class, "is_granted('ROLE_ADMIN')", Argument::withEntry('object', $dummy))->willReturn(false)->shouldBeCalledOnce();

        $normalizer = $this->createHydraOperationsNormalizer($dummy, [
            new Get(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'get', hydraOperations: [new HydraOperation(method: 'DELETE', uriTemplate: '/dummies/{id}{._format}', name: 'delete', security: "is_granted('ROLE_ADMIN')")]),
            new Delete(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'delete'),
        ], [], $accessCheckerProphecy->reveal());

        $this->assertArrayNotHasKey('operation', $normalizer->normalize($dummy));
    }

    public function testNormalizeExposesTheOperationsSharingTheIriByDefault(): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $accessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $accessCheckerProphecy->isGranted(Dummy::class, "is_granted('ROLE_ADMIN')", Argument::withEntry('object', $dummy))->willReturn(false);

        $normalizer = $this->createHydraOperationsNormalizer($dummy, [
            new GetCollection(uriTemplate: '/dummies{._format}', shortName: 'Dummy', class: Dummy::class, name: 'get_collection'),
            new Get(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'get'),
            new Post(uriTemplate: '/dummies{._format}', shortName: 'Dummy', class: Dummy::class, name: 'post'),
            new Patch(uriTemplate: '/dummies/{id}', shortName: 'Dummy', class: Dummy::class, name: 'patch'),
            new Delete(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'delete', security: "is_granted('ROLE_ADMIN')"),
        ], [], $accessCheckerProphecy->reveal());

        $this->assertEquals([
            [
                '@type' => ['Operation', 'schema:FindAction'],
                'description' => 'Retrieves a Dummy resource.',
                'method' => 'GET',
                'returns' => 'Dummy',
                'title' => 'getDummy',
            ],
            [
                '@type' => 'Operation',
                'description' => 'Updates the Dummy resource.',
                'expects' => 'Dummy',
                'expectsHeader' => [['headerName' => 'Content-Type', 'possibleValue' => []]],
                'method' => 'PATCH',
                'returns' => 'Dummy',
                'title' => 'patchDummy',
            ],
        ], $normalizer->normalize($dummy)['operation']);
    }

    #[DataProvider('disabledHydraOperationsProvider')]
    public function testNormalizeWithDisabledHydraOperations(?false $hydraOperations, array $defaultContext): void
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $normalizer = $this->createHydraOperationsNormalizer($dummy, [
            new Get(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'get', hydraOperations: $hydraOperations),
            new Delete(uriTemplate: '/dummies/{id}{._format}', shortName: 'Dummy', class: Dummy::class, name: 'delete'),
        ], $defaultContext);

        $this->assertArrayNotHasKey('operation', $normalizer->normalize($dummy));
    }

    public static function disabledHydraOperationsProvider(): iterable
    {
        yield 'disabled on the operation' => [false, []];
        yield 'disabled by the configuration' => [null, ['hydra_operations' => false]];
    }

    /**
     * @param list<Get|GetCollection|Post|Patch|Delete> $operations
     */
    private function createHydraOperationsNormalizer(Dummy $dummy, array $operations, array $defaultContext, ?ResourceAccessCheckerInterface $resourceAccessChecker = null): ItemNormalizer
    {
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(shortName: 'Dummy', class: Dummy::class, operations: $operations),
        ]));

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, Argument::any())->willReturn(new PropertyNameCollection(['name']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', Argument::type('array'))->willReturn((new ApiProperty())->withReadable(true));

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy, UrlGeneratorInterface::ABS_PATH, null, Argument::any())->willReturn('/dummies/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilderProphecy->getResourceContextUri(Dummy::class)->willReturn('/contexts/Dummy');

        $normalizer = new ItemNormalizer(
            $resourceMetadataCollectionFactoryProphecy->reveal(),
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $contextBuilderProphecy->reveal(),
            null,
            null,
            null,
            ['hydra_prefix' => false] + $defaultContext,
            $resourceAccessChecker,
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        return $normalizer;
    }
}
