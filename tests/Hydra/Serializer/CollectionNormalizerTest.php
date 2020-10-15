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

namespace ApiPlatform\Core\Tests\Hydra\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Hydra\Serializer\CollectionNormalizer;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\Foo;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportsNormalize()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $iriConvert = $this->prophesize(IriConverterInterface::class);
        $contextBuilder = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilder->getResourceContextUri('Foo')->willReturn('/contexts/Foo');
        $iriConvert->getIriFromResourceClass('Foo')->willReturn('/foos');

        $normalizer = new CollectionNormalizer($contextBuilder->reveal(), $resourceClassResolverProphecy->reveal(), $iriConvert->reveal());

        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization(new \ArrayObject(), CollectionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization([], 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \ArrayObject(), 'xml'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizeResourceCollection()
    {
        $fooOne = new Foo();
        $fooOne->id = 1;
        $fooOne->bar = 'baz';

        $fooThree = new Foo();
        $fooThree->id = 3;
        $fooThree->bar = 'bzz';

        $data = [$fooOne, $fooThree];

        $normalizedFooOne = [
            '@id' => '/foos/1',
            '@type' => 'Foo',
            'bar' => 'baz',
        ];

        $normalizedFooThree = [
            '@id' => '/foos/3',
            '@type' => 'Foo',
            'bar' => 'bzz',
        ];

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilderProphecy->getResourceContextUri(Foo::class)->willReturn('/contexts/Foo');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, Foo::class)->willReturn(Foo::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Foo::class)->willReturn('/foos');

        $delegateNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $delegateNormalizerProphecy->normalize($fooOne, CollectionNormalizer::FORMAT, Argument::allOf(
            Argument::withEntry('resource_class', Foo::class),
            Argument::withEntry('api_sub_level', true)
        ))->willReturn($normalizedFooOne);
        $delegateNormalizerProphecy->normalize($fooThree, CollectionNormalizer::FORMAT, Argument::allOf(
            Argument::withEntry('resource_class', Foo::class),
            Argument::withEntry('api_sub_level', true)
        ))->willReturn($normalizedFooThree);

        $normalizer = new CollectionNormalizer($contextBuilderProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $iriConverterProphecy->reveal());
        $normalizer->setNormalizer($delegateNormalizerProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'collection_operation_name' => 'get',
            'operation_type' => OperationType::COLLECTION,
            'resource_class' => Foo::class,
        ]);

        $this->assertEquals([
            '@context' => '/contexts/Foo',
            '@id' => '/foos',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                $normalizedFooOne,
                $normalizedFooThree,
            ],
            'hydra:totalItems' => 2,
        ], $actual);
    }

    public function testNormalizeNonResourceCollection()
    {
        $notAResourceA = new NotAResource('A', 'buzz');
        $notAResourceB = new NotAResource('B', 'bzzt');

        $data = [$notAResourceA, $notAResourceB];

        $normalizedNotAResourceA = [
            'foo' => 'A',
            'bar' => 'buzz',
        ];

        $normalizedNotAResourceB = [
            'foo' => 'B',
            'bar' => 'bzzt',
        ];

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, null)->willThrow(InvalidArgumentException::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $delegateNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $delegateNormalizerProphecy->normalize($notAResourceA, CollectionNormalizer::FORMAT, Argument::any())->willReturn($normalizedNotAResourceA);
        $delegateNormalizerProphecy->normalize($notAResourceB, CollectionNormalizer::FORMAT, Argument::any())->willReturn($normalizedNotAResourceB);

        $normalizer = new CollectionNormalizer($contextBuilderProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $iriConverterProphecy->reveal());
        $normalizer->setNormalizer($delegateNormalizerProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
        ]);

        $this->assertEquals([
            $normalizedNotAResourceA,
            $normalizedNotAResourceB,
        ], $actual);
    }

    public function testNormalizeSubLevelResourceCollection()
    {
        $fooOne = new Foo();
        $fooOne->id = 1;
        $fooOne->bar = 'baz';

        $fooThree = new Foo();
        $fooThree->id = 3;
        $fooThree->bar = 'bzz';

        $data = [$fooOne, $fooThree];

        $normalizedFooOne = [
            '@id' => '/foos/1',
            '@type' => 'Foo',
            'bar' => 'baz',
        ];

        $normalizedFooThree = [
            '@id' => '/foos/3',
            '@type' => 'Foo',
            'bar' => 'bzz',
        ];

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $delegateNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $delegateNormalizerProphecy->normalize($fooOne, CollectionNormalizer::FORMAT, Argument::allOf(
            Argument::withEntry('resource_class', Foo::class),
            Argument::withEntry('api_sub_level', true)
        ))->willReturn($normalizedFooOne);
        $delegateNormalizerProphecy->normalize($fooThree, CollectionNormalizer::FORMAT, Argument::allOf(
            Argument::withEntry('resource_class', Foo::class),
            Argument::withEntry('api_sub_level', true)
        ))->willReturn($normalizedFooThree);

        $normalizer = new CollectionNormalizer($contextBuilderProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $iriConverterProphecy->reveal());
        $normalizer->setNormalizer($delegateNormalizerProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'collection_operation_name' => 'get',
            'operation_type' => OperationType::COLLECTION,
            'resource_class' => Foo::class,
            'api_sub_level' => true,
        ]);

        $this->assertEquals([
            $normalizedFooOne,
            $normalizedFooThree,
        ], $actual);
    }

    public function testNormalizeSubLevelNonResourceCollection()
    {
        $notAResourceA = new NotAResource('A', 'buzz');
        $notAResourceB = new NotAResource('B', 'bzzt');

        $data = [$notAResourceA, $notAResourceB];

        $normalizedNotAResourceA = [
            'foo' => 'A',
            'bar' => 'buzz',
        ];

        $normalizedNotAResourceB = [
            'foo' => 'B',
            'bar' => 'bzzt',
        ];

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $delegateNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $delegateNormalizerProphecy->normalize($notAResourceA, CollectionNormalizer::FORMAT, Argument::any())->willReturn($normalizedNotAResourceA);
        $delegateNormalizerProphecy->normalize($notAResourceB, CollectionNormalizer::FORMAT, Argument::any())->willReturn($normalizedNotAResourceB);

        $normalizer = new CollectionNormalizer($contextBuilderProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $iriConverterProphecy->reveal());
        $normalizer->setNormalizer($delegateNormalizerProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'api_sub_level' => true,
        ]);

        $this->assertEquals([
            $normalizedNotAResourceA,
            $normalizedNotAResourceB,
        ], $actual);
    }

    public function testNormalizePaginator()
    {
        $this->assertEquals(
            [
                '@context' => '/contexts/Foo',
                '@id' => '/foo/1',
                '@type' => 'hydra:Collection',
                'hydra:member' => [
                    [
                        'name' => 'Kévin',
                        'friend' => 'Smail',
                    ],
                ],
                'hydra:totalItems' => 1312.,
            ],
            $this->normalizePaginator()
        );
    }

    public function testNormalizePartialPaginator()
    {
        $this->assertEquals(
            [
                '@context' => '/contexts/Foo',
                '@id' => '/foo/1',
                '@type' => 'hydra:Collection',
                'hydra:member' => [
                    0 => [
                        'name' => 'Kévin',
                        'friend' => 'Smail',
                    ],
                ],
            ],
            $this->normalizePaginator(true)
        );
    }

    private function normalizePaginator($partial = false)
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        if ($partial) {
            $paginatorProphecy = $this->prophesize(PartialPaginatorInterface::class);
        }

        if (!$partial) {
            $paginatorProphecy->getTotalItems()->willReturn(1312);
        }

        $paginatorProphecy->rewind()->will(function () {});
        $paginatorProphecy->valid()->willReturn(true, false);
        $paginatorProphecy->current()->willReturn('foo');
        $paginatorProphecy->next()->will(function () {});

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->willImplement(NormalizerInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginatorProphecy, 'Foo')->willReturn('Foo');

        $iriConvert = $this->prophesize(IriConverterInterface::class);
        $iriConvert->getIriFromResourceClass('Foo')->willReturn('/foo/1');

        $contextBuilder = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilder->getResourceContextUri('Foo')->willReturn('/contexts/Foo');

        $itemNormalizer = $this->prophesize(AbstractItemNormalizer::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'jsonld_has_context' => true,
            'api_sub_level' => true,
            'resource_class' => 'Foo',
        ])->willReturn(['name' => 'Kévin', 'friend' => 'Smail']);

        $normalizer = new CollectionNormalizer($contextBuilder->reveal(), $resourceClassResolverProphecy->reveal(), $iriConvert->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        return $normalizer->normalize($paginatorProphecy->reveal(), CollectionNormalizer::FORMAT, [
            'resource_class' => 'Foo',
        ]);
    }

    public function testNormalizeIriOnlyResourceCollection(): void
    {
        $fooOne = new Foo();
        $fooOne->id = 1;
        $fooOne->bar = 'baz';

        $fooThree = new Foo();
        $fooThree->id = 3;
        $fooThree->bar = 'bzz';

        $data = [$fooOne, $fooThree];

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilderProphecy->getResourceContextUri(Foo::class)->willReturn('/contexts/Foo');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, Foo::class)->willReturn(Foo::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Foo::class)->willReturn('/foos');
        $iriConverterProphecy->getIriFromItem($fooOne)->willReturn('/foos/1');
        $iriConverterProphecy->getIriFromItem($fooThree)->willReturn('/foos/3');

        $delegateNormalizerProphecy = $this->prophesize(NormalizerInterface::class);

        $normalizer = new CollectionNormalizer($contextBuilderProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $iriConverterProphecy->reveal());
        $normalizer->setNormalizer($delegateNormalizerProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'collection_operation_name' => 'get',
            'iri_only' => true,
            'resource_class' => Foo::class,
        ]);

        $this->assertSame([
            '@context' => '/contexts/Foo',
            '@id' => '/foos',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                '/foos/1',
                '/foos/3',
            ],
            'hydra:totalItems' => 2,
        ], $actual);
    }

    public function testNormalizeIriOnlyEmbedContextResourceCollection(): void
    {
        $fooOne = new Foo();
        $fooOne->id = 1;
        $fooOne->bar = 'baz';

        $fooThree = new Foo();
        $fooThree->id = 3;
        $fooThree->bar = 'bzz';

        $data = [$fooOne, $fooThree];

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilderProphecy->getResourceContext(Foo::class)->willReturn([
            '@vocab' => 'http://localhost:8080/docs.jsonld#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'hydra:member' => [
                '@type' => '@id',
            ],
        ]);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, Foo::class)->willReturn(Foo::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Foo::class)->willReturn('/foos');
        $iriConverterProphecy->getIriFromItem($fooOne)->willReturn('/foos/1');
        $iriConverterProphecy->getIriFromItem($fooThree)->willReturn('/foos/3');

        $delegateNormalizerProphecy = $this->prophesize(NormalizerInterface::class);

        $normalizer = new CollectionNormalizer($contextBuilderProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $iriConverterProphecy->reveal());
        $normalizer->setNormalizer($delegateNormalizerProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'collection_operation_name' => 'get',
            'iri_only' => true,
            'jsonld_embed_context' => true,
            'resource_class' => Foo::class,
        ]);

        $this->assertSame([
            '@context' => [
                '@vocab' => 'http://localhost:8080/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'hydra:member' => [
                    '@type' => '@id',
                ],
            ],
            '@id' => '/foos',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                '/foos/1',
                '/foos/3',
            ],
            'hydra:totalItems' => 2,
        ], $actual);
    }
}
