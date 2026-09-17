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

namespace ApiPlatform\Hydra\Tests\Serializer;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Hydra\IriTemplate;
use ApiPlatform\Hydra\IriTemplateMapping;
use ApiPlatform\Hydra\PartialCollectionView;
use ApiPlatform\Hydra\Serializer\CollectionObjectNormalizer;
use ApiPlatform\Hydra\Tests\Fixtures\Foo;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CollectionObjectNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = $this->createNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new Collection(), CollectionObjectNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new Collection(), 'json'));
        $this->assertFalse($normalizer->supportsNormalization([], CollectionObjectNormalizer::FORMAT));
    }

    public function testGetSupportedTypes(): void
    {
        $normalizer = $this->createNormalizer();

        $this->assertSame([Collection::class => true], $normalizer->getSupportedTypes(CollectionObjectNormalizer::FORMAT));
        $this->assertSame([], $normalizer->getSupportedTypes('json'));
    }

    public function testNormalizeBuildsContextAndIdWhenNotSet(): void
    {
        $collection = new Collection();
        $collection->member = [];

        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder->expects($this->once())->method('getResourceContextUri')->with(Foo::class)->willReturn('/contexts/Foo');

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('getResourceClass')->with(null, Foo::class)->willReturn(Foo::class);

        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->once())->method('getIriFromResource')->with(Foo::class, UrlGeneratorInterface::ABS_PATH, null, $this->anything())->willReturn('/foos');

        $normalizer = new CollectionObjectNormalizer($contextBuilder, $resourceClassResolver, $iriConverter);

        $result = $normalizer->normalize($collection, CollectionObjectNormalizer::FORMAT, ['resource_class' => Foo::class]);

        $this->assertSame('/contexts/Foo', $result['@context']);
        $this->assertSame('/foos', $result['@id']);
        $this->assertSame('hydra:Collection', $result['@type']);
        $this->assertSame([], $result['hydra:member']);
    }

    public function testNormalizeKeepsExplicitContextAndId(): void
    {
        $collection = new Collection();
        $collection->context = '/contexts/Custom';
        $collection->id = '/custom_id';
        $collection->member = [];

        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder->expects($this->never())->method('getResourceContextUri');

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('getResourceClass')->willReturn(Foo::class);

        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->never())->method('getIriFromResource');

        $normalizer = new CollectionObjectNormalizer($contextBuilder, $resourceClassResolver, $iriConverter);

        $result = $normalizer->normalize($collection, CollectionObjectNormalizer::FORMAT, ['resource_class' => Foo::class]);

        $this->assertSame('/contexts/Custom', $result['@context']);
        $this->assertSame('/custom_id', $result['@id']);
    }

    public function testNormalizeCountsCountableMemberWhenTotalItemsUninitialized(): void
    {
        $foo1 = new Foo();
        $foo2 = new Foo();

        $collection = new Collection();
        $collection->member = [$foo1, $foo2];

        $normalizer = $this->createNormalizer();
        $itemNormalizer = $this->createStub(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn(['@id' => '/foos/1']);
        $normalizer->setNormalizer($itemNormalizer);

        $result = $normalizer->normalize($collection, CollectionObjectNormalizer::FORMAT, ['resource_class' => Foo::class]);

        $this->assertSame(2, $result['hydra:totalItems']);
    }

    public function testNormalizeOmitsTotalItemsForNonCountableMember(): void
    {
        $collection = new Collection();
        $collection->member = (static function (): \Generator {
            yield new Foo();
        })();

        $normalizer = $this->createNormalizer();
        $itemNormalizer = $this->createStub(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->willReturn(['@id' => '/foos/1']);
        $normalizer->setNormalizer($itemNormalizer);

        $result = $normalizer->normalize($collection, CollectionObjectNormalizer::FORMAT, ['resource_class' => Foo::class]);

        $this->assertArrayNotHasKey('hydra:totalItems', $result);
    }

    public function testNormalizeSearch(): void
    {
        $collection = new Collection();
        $collection->member = [];
        $collection->search = new IriTemplate(
            'BasicRepresentation',
            [
                new IriTemplateMapping('foo', 'foo', true),
                new IriTemplateMapping('bar', 'bar'),
            ],
            '/foos{?foo}',
        );

        $normalizer = $this->createNormalizer();

        $result = $normalizer->normalize($collection, CollectionObjectNormalizer::FORMAT, ['resource_class' => Foo::class]);

        $this->assertSame([
            '@type' => 'hydra:IriTemplate',
            'hydra:template' => '/foos{?foo}',
            'hydra:variableRepresentation' => 'BasicRepresentation',
            'hydra:mapping' => [
                ['@type' => 'IriTemplateMapping', 'variable' => 'foo', 'property' => 'foo', 'required' => true],
                ['@type' => 'IriTemplateMapping', 'variable' => 'bar', 'property' => 'bar', 'required' => false],
            ],
        ], $result['hydra:search']);
    }

    public function testNormalizeWithCustomHydraPrefix(): void
    {
        $collection = new Collection();
        $collection->member = [];
        $collection->view = new PartialCollectionView('/foos?page=1', first: '/foos?page=1', last: '/foos?page=1');

        $normalizer = $this->createNormalizer();

        $result = $normalizer->normalize($collection, CollectionObjectNormalizer::FORMAT, [
            'resource_class' => Foo::class,
            ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false,
        ]);

        $this->assertSame('Collection', $result['@type']);
        $this->assertArrayHasKey('totalItems', $result);
        $this->assertArrayHasKey('member', $result);
        $this->assertArrayHasKey('view', $result);
        $this->assertSame('PartialCollectionView', $result['view']['@type']);
        $this->assertArrayNotHasKey('hydra:view', $result);
    }

    public function testNormalizeWithoutResourceClassThrows(): void
    {
        $normalizer = $this->createNormalizer();

        $this->expectException(UnexpectedValueException::class);

        $normalizer->normalize(new Collection(), CollectionObjectNormalizer::FORMAT, []);
    }

    private function createNormalizer(): CollectionObjectNormalizer
    {
        $contextBuilder = $this->createStub(ContextBuilderInterface::class);
        $contextBuilder->method('getResourceContextUri')->willReturn('/contexts/Foo');

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('getResourceClass')->willReturn(Foo::class);

        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')->willReturn('/foos');

        return new CollectionObjectNormalizer($contextBuilder, $resourceClassResolver, $iriConverter);
    }
}
