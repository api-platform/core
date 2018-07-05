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

namespace ApiPlatform\Core\Tests\Identifier;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Identifier\IdentifierConverter;
use ApiPlatform\Core\Identifier\Normalizer\DateTimeIdentifierDenormalizer;
use ApiPlatform\Core\Identifier\Normalizer\IntegerDenormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class IdentifierConverterTest extends TestCase
{
    public function testCompositeIdentifier()
    {
        $identifier = 'a=1;c=2;d=2015-04-05';
        $class = 'Dummy';

        $integerPropertyMetadata = (new PropertyMetadata())->withIdentifier(true)->withType(new Type(Type::BUILTIN_TYPE_INT));
        $identifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true);
        $dateIdentifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true)->withType(new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTime::class));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create($class, 'a')->shouldBeCalled()->willReturn($integerPropertyMetadata);
        $propertyMetadataFactory->create($class, 'c')->shouldBeCalled()->willReturn($identifierPropertyMetadata);
        $propertyMetadataFactory->create($class, 'd')->shouldBeCalled()->willReturn($dateIdentifierPropertyMetadata);

        $identifiersExtractor = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractor->getIdentifiersFromResourceClass($class)->willReturn(['a', 'c', 'd']);

        $identifierDenormalizers = [new IntegerDenormalizer(), new DateTimeIdentifierDenormalizer()];

        $identifierDenormalizer = new IdentifierConverter($identifiersExtractor->reveal(), $propertyMetadataFactory->reveal(), $identifierDenormalizers);

        $result = $identifierDenormalizer->convert($identifier, $class);
        $this->assertEquals(['a' => 1, 'c' => '2', 'd' => new \DateTime('2015-04-05')], $result);
        $this->assertSame(1, $result['a']);
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
        $identifierDenormalizer = new IdentifierConverter($identifiersExtractor->reveal(), $propertyMetadataFactory->reveal(), $identifierDenormalizers);

        $this->assertEquals($identifierDenormalizer->convert($identifier, $class), ['funkyid' => new \DateTime('2015-04-05')]);
    }

    public function testIntegerIdentifier()
    {
        $identifier = '42';
        $class = 'Dummy';

        $integerIdentifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true)->withType(new Type(Type::BUILTIN_TYPE_INT));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create($class, 'id')->shouldBeCalled()->willReturn($integerIdentifierPropertyMetadata);

        $identifiersExtractor = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractor->getIdentifiersFromResourceClass($class)->willReturn(['id']);

        $identifierDenormalizers = [new IntegerDenormalizer()];
        $identifierDenormalizer = new IdentifierConverter($identifiersExtractor->reveal(), $propertyMetadataFactory->reveal(), $identifierDenormalizers);

        $this->assertSame(['id' => 42], $identifierDenormalizer->convert($identifier, $class));
    }
}
