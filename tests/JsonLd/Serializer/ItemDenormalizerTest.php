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

use ApiPlatform\JsonLd\Serializer\ItemDenormalizer;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

class ItemDenormalizerTest extends TestCase
{
    use ProphecyTrait;

    public static function provideNonStringIds(): iterable
    {
        yield 'integer' => [123];
        yield 'array' => [['/related_dummies/1']];
        yield 'boolean' => [true];
    }

    #[DataProvider('provideNonStringIds')]
    public function testDenormalizeRejectsNonStringId(mixed $id): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getResourceFromIri(Argument::cetera())->shouldNotBeCalled();

        $denormalizer = new ItemDenormalizer(
            $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $iriConverterProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal()
        );

        try {
            $denormalizer->denormalize(['@id' => $id], RelatedDummy::class, ItemDenormalizer::FORMAT, ['deserialization_path' => 'relatedDummy']);
            $this->fail('A NotNormalizableValueException should have been thrown.');
        } catch (NotNormalizableValueException $e) {
            $this->assertSame('relatedDummy.@id', $e->getPath());
            $this->assertSame(['string'], $e->getExpectedTypes());
            $this->assertTrue($e->canUseMessageForUser());
        }
    }
}
