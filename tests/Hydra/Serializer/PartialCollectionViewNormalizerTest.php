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

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Hydra\Serializer\PartialCollectionViewNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PartialCollectionViewNormalizerTest extends TestCase
{
    public function testNormalizeDoesNotChangeSubLevel()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::any(), null, ['jsonld_sub_level' => true])->willReturn(['foo' => 'bar'])->shouldBeCalled();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal());
        $this->assertEquals(['foo' => 'bar'], $normalizer->normalize(new \stdClass(), null, ['jsonld_sub_level' => true]));
    }

    public function testNormalizeDoesNotChangeWhenNoFilterNorPagination()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::any(), null, Argument::type('array'))->willReturn(['foo' => 'bar'])->shouldBeCalled();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal());
        $this->assertEquals(['foo' => 'bar'], $normalizer->normalize(new \stdClass(), null, ['request_uri' => '/?page=1&pagination=1']));
    }

    public function testNormalizePaginator()
    {
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
            $this->normalizePaginator()
        );
    }

    public function testNormalizePartialaginator()
    {
        $this->assertEquals(
            [
                'foo' => 'bar',
                'hydra:view' => [
                    '@id' => '/?_page=3',
                    '@type' => 'hydra:PartialCollectionView',
                    'hydra:previous' => '/?_page=2',
                    'hydra:next' => '/?_page=4',
                ],
            ],
            $this->normalizePaginator(true)
        );
    }

    private function normalizePaginator($partial = false)
    {
        $paginatorProphecy = $this->prophesize($partial ? PartialPaginatorInterface::class : PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3)->shouldBeCalled();

        $decoratedNormalize = ['foo' => 'bar'];

        if ($partial) {
            $paginatorProphecy->getItemsPerPage()->willReturn(42)->shouldBeCalled();
            $paginatorProphecy->count()->willReturn(42)->shouldBeCalled();
        } else {
            $decoratedNormalize['hydra:totalItems'] = 40;
            $paginatorProphecy->getLastPage()->willReturn(20)->shouldBeCalled();
        }

        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::type($partial ? PartialPaginatorInterface::class : PaginatorInterface::class), null, Argument::type('array'))->willReturn($decoratedNormalize)->shouldBeCalled();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal(), '_page');

        return $normalizer->normalize($paginatorProphecy->reveal());
    }

    public function testSupportsNormalization()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->willImplement(CacheableSupportsMethodInterface::class);
        $decoratedNormalizerProphecy->supportsNormalization(Argument::any(), null)->willReturn(true)->shouldBeCalled();
        $decoratedNormalizerProphecy->hasCacheableSupportsMethod()->willReturn(true)->shouldBeCalled();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal());
        $this->assertTrue($normalizer->supportsNormalization(new \stdClass()));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testSetNormalizer()
    {
        $injectedNormalizer = $this->prophesize(NormalizerInterface::class)->reveal();

        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->willImplement(NormalizerAwareInterface::class);
        $decoratedNormalizerProphecy->setNormalizer(Argument::type(NormalizerInterface::class))->shouldBeCalled();

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal());
        $normalizer->setNormalizer($injectedNormalizer);
    }
}
