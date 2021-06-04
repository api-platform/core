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

namespace ApiPlatform\Core\Tests\Bridge\Eloquent\Serializer;

use ApiPlatform\Core\Bridge\Eloquent\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class CollectionNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private $collectionNormalizerProphecy;
    private $collectionNormalizer;

    protected function setUp(): void
    {
        $this->collectionNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $this->collectionNormalizer = new CollectionNormalizer($this->collectionNormalizerProphecy->reveal());
    }

    /**
     * @dataProvider provideSupportsNormalizationCases
     */
    public function testSupportsNormalization($data, bool $expectedResult): void
    {
        $this->collectionNormalizerProphecy->supportsNormalization($data, null)->willReturn(true);

        self::assertSame($expectedResult, $this->collectionNormalizer->supportsNormalization($data));
    }

    public function provideSupportsNormalizationCases(): \Generator
    {
        yield 'not supported' => [[], false];
        yield 'supported' => [new Collection(), true];
    }

    public function testHasCacheableSupportsMethod(): void
    {
        self::assertFalse($this->collectionNormalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize(): void
    {
        $model = new Dummy();

        $this->collectionNormalizerProphecy->normalize($model, null, [])->willReturn('normalized');

        self::assertSame('normalized', $this->collectionNormalizer->normalize($model));
    }
}
