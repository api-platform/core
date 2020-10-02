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

use ApiPlatform\Core\Identifier\IdentifierDenormalizer;
use ApiPlatform\Core\Identifier\Normalizer\DateTimeIdentifierDenormalizer;
use ApiPlatform\Core\Identifier\Normalizer\IntegerDenormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class IdentifierDenormalizerTest extends TestCase
{
    public function testSingleDateIdentifier()
    {
        $identifiers = ['funkyid' => '2015-04-05'];
        $class = 'Dummy';

        $dateIdentifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true)->withType(new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTime::class));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create($class, 'funkyid')->shouldBeCalled()->willReturn($dateIdentifierPropertyMetadata);

        $identifierDenormalizers = [new DateTimeIdentifierDenormalizer()];
        $identifierDenormalizer = new IdentifierDenormalizer($propertyMetadataFactory->reveal(), $identifierDenormalizers);

        $this->assertEquals($identifierDenormalizer->denormalize($identifiers, $class), ['funkyid' => new \DateTime('2015-04-05')]);
    }

    public function testIntegerIdentifier()
    {
        $identifiers = ['id' => '42'];
        $class = 'Dummy';

        $integerIdentifierPropertyMetadata = (new PropertyMetadata())->withIdentifier(true)->withType(new Type(Type::BUILTIN_TYPE_INT));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create($class, 'id')->shouldBeCalled()->willReturn($integerIdentifierPropertyMetadata);

        $identifierDenormalizers = [new IntegerDenormalizer()];
        $identifierDenormalizer = new IdentifierDenormalizer($propertyMetadataFactory->reveal(), $identifierDenormalizers);

        $this->assertSame(['id' => 42], $identifierDenormalizer->denormalize($identifiers, $class));
    }
}
