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

namespace ApiPlatform\Core\Tests\Identifier\Normalizer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Identifier\Normalizer\ChainIdentifierDenormalizer;
use ApiPlatform\Core\Identifier\Normalizer\DateTimeIdentifierDenormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ChainIdentifierDenormalizerTest extends TestCase
{
    public function testCompositeIdentifier()
    {
        $identifier = 'a=1;c=2;d=2015-04-05';
        $class = 'Dummy';

        $identifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true);
        $dateIdentifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true)->withType(new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTime::class));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create($class, 'a')->shouldBeCalled()->willReturn($identifierPropertyMetadata);
        $propertyMetadataFactory->create($class, 'c')->shouldBeCalled()->willReturn($identifierPropertyMetadata);
        $propertyMetadataFactory->create($class, 'd')->shouldBeCalled()->willReturn($dateIdentifierPropertyMetadata);

        $identifiersExtractor = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractor->getIdentifiersFromResourceClass($class)->willReturn(['a', 'c', 'd']);

        $identifierDenormalizers = [new DateTimeIdentifierDenormalizer()];

        $identifierDenormalizer = new ChainIdentifierDenormalizer($identifiersExtractor->reveal(), $propertyMetadataFactory->reveal(), $identifierDenormalizers);

        $this->assertEquals($identifierDenormalizer->denormalize($identifier, $class), ['a' => '1', 'c' => '2', 'd' => new \DateTime('2015-04-05')]);
    }

    public function testSingleDateIdentifier()
    {
        $identifier = '2015-04-05';
        $class = 'Dummy';

        $dateIdentifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true)->withType(new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTime::class));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create($class, 'funkyid')->shouldBeCalled()->willReturn($dateIdentifierPropertyMetadata);

        $identifiersExtractor = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractor->getIdentifiersFromResourceClass($class)->willReturn(['funkyid']);

        $identifierDenormalizers = [new DateTimeIdentifierDenormalizer()];
        $identifierDenormalizer = new ChainIdentifierDenormalizer($identifiersExtractor->reveal(), $propertyMetadataFactory->reveal(), $identifierDenormalizers);

        $this->assertEquals($identifierDenormalizer->denormalize($identifier, $class), ['funkyid' => new \DateTime('2015-04-05')]);
    }
}
