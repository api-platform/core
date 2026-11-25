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
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * @author Ali Ben Macha <contact@benmacha.tn>
 */
class ItemDenormalizerTest extends TestCase
{
    public static function provideNonStringIds(): iterable
    {
        yield 'integer' => [123];
        yield 'array' => [['/related_dummies/1']];
        yield 'boolean' => [true];
    }

    #[DataProvider('provideNonStringIds')]
    public function testDenormalizeRejectsNonStringId(mixed $id): void
    {
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->never())->method('getResourceFromIri');

        $denormalizer = new ItemDenormalizer(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $this->createStub(PropertyNameCollectionFactoryInterface::class),
            $this->createStub(PropertyMetadataFactoryInterface::class),
            $iriConverter,
            $this->createStub(ResourceClassResolverInterface::class)
        );

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the "@id" attribute must be "string"');

        $denormalizer->denormalize(['@id' => $id], RelatedDummy::class, ItemDenormalizer::FORMAT, ['deserialization_path' => 'relatedDummy']);
    }
}
