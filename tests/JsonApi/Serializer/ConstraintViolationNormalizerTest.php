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

namespace ApiPlatform\Core\Tests\JsonApi\Serializer;

use ApiPlatform\Core\JsonApi\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ConstraintViolationNormalizerTest extends TestCase
{
    public function testSupportNormalization()
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $nameConverterInterface = $this->prophesize(NameConverterInterface::class);

        $normalizer = new ConstraintViolationListNormalizer($propertyMetadataFactoryProphecy->reveal(), $nameConverterInterface->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new ConstraintViolationList(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new ConstraintViolationList(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)))->shouldBeCalled(1);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)))->shouldBeCalled(1);

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('relatedDummy', Dummy::class, 'jsonapi')->willReturn('relatedDummy')->shouldBeCalled(1);
        $nameConverterProphecy->normalize('name', Dummy::class, 'jsonapi')->willReturn('name')->shouldBeCalled(1);

        $dummy = new Dummy();

        $constraintViolationList = new ConstraintViolationList([
            new ConstraintViolation('This value should not be null.', 'This value should not be null.', [], $dummy, 'relatedDummy', null),
            new ConstraintViolation('This value should not be null.', 'This value should not be null.', [], $dummy, 'name', null),
            new ConstraintViolation('Unknown violation.', 'Unknown violation.', [], $dummy, '', ''),
        ]);

        $this->assertEquals(
            [
                'errors' => [
                    [
                        'detail' => 'This value should not be null.',
                        'source' => [
                            'pointer' => 'data/relationships/relatedDummy',
                        ],
                    ],
                    [
                        'detail' => 'This value should not be null.',
                        'source' => [
                            'pointer' => 'data/attributes/name',
                        ],
                    ],
                    [
                        'detail' => 'Unknown violation.',
                        'source' => [
                            'pointer' => 'data',
                        ],
                    ],
                ],
            ],
            (new ConstraintViolationListNormalizer($propertyMetadataFactoryProphecy->reveal(), $nameConverterProphecy->reveal()))->normalize($constraintViolationList)
        );
    }
}
