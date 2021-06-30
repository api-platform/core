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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTranslatable;
use ApiPlatform\Translation\ResourceTranslator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Type;

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
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

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
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
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
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

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
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@type' => '@id', '@id' => 'customId', 'foo' => 'bar']));
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
        $this->propertyNameCollectionFactoryProphecy->create($this->entityClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($this->entityClass, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withJsonldContext(['@reverse' => 'parent']));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

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

    public function testResourceContextWithLanguage(): void
    {
        $resourceClass = DummyTranslatable::class;

        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection('DummyTranslatable', [
            (new ApiResource())
                ->withShortName('DummyTranslatable')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyTranslatable')])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection([]));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');
        $requestStack = new RequestStack();
        $request = new Request();
        $request->setLocale('fr');
        $requestStack->push($request);
        $resourceTranslator = new ResourceTranslator($requestStack, PropertyAccess::createPropertyAccessor(), $this->resourceMetadataCollectionFactoryProphecy->reveal());

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), null, null, $resourceTranslator);

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            '@language' => 'fr',
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($resourceClass));
    }

    public function testResourceContextWithAllTranslationsEnabled(): void
    {
        $resourceClass = DummyTranslatable::class;

        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection('DummyTranslatable', [
            (new ApiResource())
                ->withShortName('DummyTranslatable')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('DummyTranslatable')->withTranslation(['all_translations_enabled' => true])])),
        ]));
        $this->propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create($resourceClass, 'dummyPropertyA', Argument::type('array'))->willReturn(new ApiProperty());
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');
        $resourceTranslator = new ResourceTranslator(new RequestStack(), PropertyAccess::createPropertyAccessor(), $this->resourceMetadataCollectionFactoryProphecy->reveal());

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), null, null, $resourceTranslator);

        $expected = [
            '@vocab' => '#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'dummyPropertyA' => [
                '@container' => '@language',
                '@id' => 'DummyTranslatable/dummyPropertyA',
            ],
        ];

        $this->assertEquals($expected, $contextBuilder->getResourceContext($resourceClass));
    }

    public function testAnonymousResourceContext(): void
    {
        $dummy = new Dummy();
        $this->propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(Dummy::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true)->withGenId(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');
        $this->iriConverterProphecy->getIriFromResource($dummy)->willreturn('/.well-known/genid/1');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal(), $this->iriConverterProphecy->reveal());

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
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

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

    public function testAnonymousResourceContextWithApiResource(): void
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $this->resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())
                ->withShortName('Dummy')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('Dummy')])),
        ]));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => [
                '@vocab' => '#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'dummyPropertyA' => 'OutputDto/dummyPropertyA',
            ],
            '@id' => '/dummies',
            '@type' => 'Dummy',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy', 'api_resource' => new Dummy()]));
    }

    public function testAnonymousResourceContextWithApiResourceHavingContext(): void
    {
        $output = new OutputDto();
        $this->propertyNameCollectionFactoryProphecy->create(OutputDto::class)->willReturn(new PropertyNameCollection(['dummyPropertyA']));
        $this->propertyMetadataFactoryProphecy->create(OutputDto::class, 'dummyPropertyA', Argument::type('array'))->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withDescription('Dummy property A')->withReadable(true)->withWritable(true)->withReadableLink(true)->withWritableLink(true));
        $this->urlGeneratorProphecy->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('');

        $this->resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())
                ->withShortName('Dummy')
                ->withOperations(new Operations(['get' => (new Get())->withShortName('Dummy')])),
        ]));

        $contextBuilder = new ContextBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->urlGeneratorProphecy->reveal());

        $expected = [
            '@id' => '/dummies',
            '@type' => 'Dummy',
        ];

        $this->assertEquals($expected, $contextBuilder->getAnonymousResourceContext($output, ['iri' => '/dummies', 'name' => 'Dummy', 'api_resource' => new Dummy(), 'has_context' => true]));
    }
}
