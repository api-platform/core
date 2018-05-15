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
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Hydra\Serializer\CollectionNormalizer;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionNormalizerTest extends TestCase
{
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
    }

    public function testNormalizeApiSubLevel()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(['foo' => 'bar'], null, true)->willReturn('Foo')->shouldBeCalled();

        $iriConvert = $this->prophesize(IriConverterInterface::class);
        $contextBuilder = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilder->getResourceContextUri('Foo')->willReturn('/contexts/Foo');
        $iriConvert->getIriFromResourceClass('Foo')->willReturn('/foo/1');
        $itemNormalizer = $this->prophesize(AbstractItemNormalizer::class);
        $itemNormalizer->normalize('bar', null, ['jsonld_has_context' => true, 'api_sub_level' => true, 'resource_class' => 'Foo'])->willReturn(22);

        $normalizer = new CollectionNormalizer($contextBuilder->reveal(), $resourceClassResolverProphecy->reveal(), $iriConvert->reveal());

        $normalizer->setNormalizer($itemNormalizer->reveal());
        $this->assertEquals(['@context' => '/contexts/Foo', '@id' => '/foo/1', '@type' => 'hydra:Collection', 'hydra:member' => [0 => '22'], 'hydra:totalItems' => 1], $normalizer->normalize(['foo' => 'bar'], null));
    }

    public function testNormalizePaginator()
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
                'hydra:totalItems' => 1312.,
            ],
            $this->normalizePaginator(false)
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
        $paginatorProphecy = $this->prophesize($partial ? PartialPaginatorInterface::class : PaginatorInterface::class);

        if (!$partial) {
            $paginatorProphecy->getTotalItems()->willReturn(1312)->shouldBeCalled();
        }

        $paginatorProphecy->rewind()->shouldBeCalled();
        $paginatorProphecy->valid()->willReturn(true, false)->shouldBeCalled();
        $paginatorProphecy->current()->willReturn('foo')->shouldBeCalled();
        $paginatorProphecy->next()->willReturn()->shouldBeCalled();

        $paginator = $paginatorProphecy->reveal();

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->willImplement(NormalizerInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, null, true)->willReturn('Foo')->shouldBeCalled();

        $iriConvert = $this->prophesize(IriConverterInterface::class);
        $iriConvert->getIriFromResourceClass('Foo')->willReturn('/foo/1');

        $contextBuilder = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilder->getResourceContextUri('Foo')->willReturn('/contexts/Foo');

        $itemNormalizer = $this->prophesize(AbstractItemNormalizer::class);
        $itemNormalizer->normalize('foo', null, ['jsonld_has_context' => true, 'api_sub_level' => true, 'resource_class' => 'Foo'])->willReturn(['name' => 'Kévin', 'friend' => 'Smail']);

        $normalizer = new CollectionNormalizer($contextBuilder->reveal(), $resourceClassResolverProphecy->reveal(), $iriConvert->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        return $normalizer->normalize($paginator);
    }
}
