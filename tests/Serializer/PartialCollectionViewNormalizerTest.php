<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Hydra\Serializer\PartialCollectionViewNormalizer;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PartialCollectionViewNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizeDoesNotChangeSubLevel()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::any(), null, ['jsonld_sub_level' => true])->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $decoratedNormalizer = $decoratedNormalizerProphecy->reveal();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizer);
        $this->assertEquals(['foo' => 'bar'], $normalizer->normalize(new \stdClass(), null, ['jsonld_sub_level' => true]));
    }

    public function testNormalizeDoesNotChangeWhenNoFilterNorPagination()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::any(), null, Argument::type('array'))->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $decoratedNormalizer = $decoratedNormalizerProphecy->reveal();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizer);
        $this->assertEquals(['foo' => 'bar'], $normalizer->normalize(new \stdClass(), null, ['request_uri' => '/?page=1&pagination=1']));
    }

    public function testNormalizePaginator()
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3)->shouldBeCalled();
        $paginatorProphecy->getLastPage()->willReturn(20)->shouldBeCalled();
        $paginator = $paginatorProphecy->reveal();

        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::type(PaginatorInterface::class), null, Argument::type('array'))->willReturn(['hydra:totalItems' => 40, 'foo' => 'bar'])->shouldBeCalled();
        $decoratedNormalizer = $decoratedNormalizerProphecy->reveal();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizer, '_page');
        $this->assertEquals(
            [
                'hydra:totalItems' => 40,
                'foo' => 'bar',
                'hydra:view' => [
                    '@id' => '/?_page=3',
                    '@type' => 'hydra:PartialCollectionView',
                    'hydra:first' => '/?_page=1',
                    'hydra:last' => '/?_page=20',
                    'hydra:previous' => '/?_page=2',
                    'hydra:next' => '/?_page=4',


                ],

            ],
            $normalizer->normalize($paginator)
        );
    }

    public function testSupportsNormalization()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->supportsNormalization(Argument::any(), null)->willReturn(true)->shouldBeCalled();
        $decoratedNormalizer = $decoratedNormalizerProphecy->reveal();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizer);
        $this->assertTrue($normalizer->supportsNormalization(new \stdClass()));
    }

    public function testSetSerializer()
    {
        $serializer = $this->prophesize(SerializerInterface::class)->reveal();

        $decoratedNormalizerProphecy = $this
            ->prophesize(NormalizerInterface::class)
            ->willImplement(SerializerAwareInterface::class);
        $decoratedNormalizerProphecy->setSerializer(Argument::type(SerializerInterface::class))->shouldBeCalled();
        $decoratedNormalizer = $decoratedNormalizerProphecy->reveal();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizer);
        $normalizer->setSerializer($serializer);
    }
}
