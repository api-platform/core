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

namespace ApiPlatform\Tests\JsonLd;

use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\SubtypeResolver;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Markus Mächler <markus.maechler@bithost.ch>
 */
class ContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    private string $entityClass;
    private ObjectProphecy $resourceNameCollectionFactoryProphecy;
    private ObjectProphecy $resourceMetadataCollectionFactoryProphecy;
    private ObjectProphecy $propertyNameCollectionFactoryProphecy;
    private ObjectProphecy $propertyMetadataFactoryProphecy;
    private ObjectProphecy $iriConverterProphecy;
    private ObjectProphecy $urlGeneratorProphecy;

    protected function setUp(): void
    {
        $this->entityClass = '\Dummy\DummyEntity';
        $this->resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $this->propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
    }

    public function testResourceContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->expects($this->once())->method('create')->with($this->entityClass, [])->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $propertyNameCollectionFactory, $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => 'DummyEntity/dummyPropertyA',
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testIriOnlyResourceContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')->withNormalizationContext(['iri_only' => true])])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'hydra:member' => [
                '@type' => '@id',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testResourceContextWithJsonldContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->expects($this->once())->method('create')->with($this->entityClass, [])->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $propertyNameCollectionFactory, $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@type' => '@id',
                '@id' => 'customId',
                'foo' => 'bar',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testGetEntryPointContext(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create('dummyPropertyA')->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyEntity' => [
                '@type' => '@id',
                '@id' => 'Entrypoint/dummyEntity',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getEntrypointContext());
    }

    public function testResourceContextWithReverse(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->expects($this->once())->method('create')->with($this->entityClass, [])->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@reverse' => 'parent']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $propertyNameCollectionFactory, $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@id' => 'DummyEntity/dummyPropertyA',
                '@reverse' => 'parent',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testAnonymousResourceContext(): void
    {
        $dummy = new Dummy();
        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->expects($this->once())->method('create')->with(Dummy::class, [])->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withGenId(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');
        $this->iriConverterProphecy->getIriFromResource($dummy)->willreturn('/.well-known/genid/1');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $propertyNameCollectionFactory, $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), $this->iriConverterProphecy->reveal());

        $context = $contextBuilder->getAnonymousResourceContext($dummy);
        $this->assertSame('Dummy', $context['@type']);
        $this->assertStringStartsWith('/.well-known/genid', $context['@id']);
        $this->assertEquals([
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => 'Dummy/dummyPropertyA',
        ], $context['@context']);
    }

    public function testAnonymousResourceContextWithIri(): void
    {
        $output = new OutputDto();
        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->expects($this->once())->method('create')->with(OutputDto::class, [])->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $propertyNameCollectionFactory, $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'OutputDto/dummyPropertyA',
            ],
            '@id' => '/dummies',
            '@type' => 'OutputDto',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy']));
    }

    public function testAnonymousResourceContextIsNotComputedWhenTheDocumentAlreadyHasOne(): void
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->shouldNotBeCalled();
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $this->assertSame(['@type' => 'OutputDto', '@id' => '/dummies'], $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'has_context' => true]));
    }

    public function testResourceContextWithoutHydraPrefix(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy->create($this->entityClass)->willReturn(new ResourceMetadataCollection('DummyEntity', [
            (new ApiResource())
                ->withShortName('DummyEntity')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyEntity')])),
        ]));
        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->expects($this->once())->method('create')->with($this->entityClass, [])->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withNativeType(Type::string())->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $propertyNameCollectionFactory, $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), null, null, [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false]);

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => 'DummyEntity/dummyPropertyA',
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($this->entityClass));
    }

    public function testSiblingSubtypesShareTheTermOfAProperty(): void
    {
        $context = $this->createSubtypeContextBuilder()->getResourceContext(ContextDiscriminatedVehicle::class);

        $this->assertSame('Vehicle/seats', $context['seats']);
        $this->assertSame('Vehicle/kind', $context['kind']);
    }

    public function testSiblingSubtypesWithContradictoryIrisThrow(): void
    {
        $contextBuilder = $this->createSubtypeContextBuilder([ContextDiscriminatedCar::class => ['https://schema.org/seatingCapacity'], ContextDiscriminatedBus::class => ['https://schema.org/numberOfSeats']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('The subtypes "%s" and "%s" of the resource "%s" give the "seats" property different "iris"', ContextDiscriminatedCar::class, ContextDiscriminatedBus::class, ContextDiscriminatedVehicle::class));

        $contextBuilder->getResourceContext(ContextDiscriminatedVehicle::class);
    }

    public function testSiblingSubtypesWithDifferentTermDefinitionsThrow(): void
    {
        $contextBuilder = $this->createSubtypeContextBuilder(seatsJsonldContext: [ContextDiscriminatedBus::class => ['@type' => 'xsd:integer']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('The subtypes "%s" and "%s" of the resource "%s" define the "seats" property differently in JSON-LD', ContextDiscriminatedCar::class, ContextDiscriminatedBus::class, ContextDiscriminatedVehicle::class));

        $contextBuilder->getResourceContext(ContextDiscriminatedVehicle::class);
    }

    /**
     * @param array<class-string, list<string>>          $seatsIris
     * @param array<class-string, array<string, string>> $seatsJsonldContext
     */
    private function createSubtypeContextBuilder(array $seatsIris = [], array $seatsJsonldContext = []): ContextBuilder
    {
        $resourceNameCollectionFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([ContextDiscriminatedVehicle::class]));

        $resourceMetadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(ContextDiscriminatedVehicle::class, [
            (new ApiResource())->withShortName('Vehicle')->withOperations(new Operations(['get' => (new Get())->withShortName('Vehicle')])),
        ]));

        $propertyNames = [ContextDiscriminatedVehicle::class => ['name'], ContextDiscriminatedCar::class => ['name', 'seats'], ContextDiscriminatedBus::class => ['name', 'seats']];
        $propertyNameCollectionFactory = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')->willReturnCallback(static fn (string $class): PropertyNameCollection => new PropertyNameCollection($propertyNames[$class]));

        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturnCallback(static fn (string $class, string $property): ApiProperty => new ApiProperty(readable: true, nativeType: Type::string(), iris: 'seats' === $property ? $seatsIris[$class] ?? null : null, jsonldContext: 'seats' === $property ? $seatsJsonldContext[$class] ?? null : null));

        $mapping = new ClassDiscriminatorMapping('kind', ['car' => ContextDiscriminatedCar::class, 'bus' => ContextDiscriminatedBus::class]);
        $classDiscriminatorResolver = $this->createStub(ClassDiscriminatorResolverInterface::class);
        $classDiscriminatorResolver->method('getMappingForClass')->willReturnCallback(static fn (string $class): ?ClassDiscriminatorMapping => ContextDiscriminatedVehicle::class === $class ? $mapping : null);

        return new ContextBuilder($resourceNameCollectionFactory, $resourceMetadataCollectionFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, $this->createStub(UrlGeneratorInterface::class), subtypeResolver: new SubtypeResolver($classDiscriminatorResolver, $resourceNameCollectionFactory, $resourceMetadataCollectionFactory));
    }
}

class ContextDiscriminatedVehicle
{
}

class ContextDiscriminatedCar extends ContextDiscriminatedVehicle
{
}

class ContextDiscriminatedBus extends ContextDiscriminatedVehicle
{
}
