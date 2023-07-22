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

namespace ApiPlatform\Tests\JsonApi\Serializer;

use ApiPlatform\JsonApi\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ConstraintViolationNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @group legacy
     */
    public function testSupportNormalization(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $nameConverterInterface = $this->prophesize(NameConverterInterface::class);

        $normalizer = new ConstraintViolationListNormalizer($propertyMetadataFactoryProphecy->reveal(), $nameConverterInterface->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new ConstraintViolationList(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new ConstraintViolationList(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)]))->shouldBeCalledTimes(1);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name')->willReturn((new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]))->shouldBeCalledTimes(1);

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('relatedDummy', Dummy::class, 'jsonapi')->willReturn('relatedDummy')->shouldBeCalledTimes(1);
        $nameConverterProphecy->normalize('name', Dummy::class, 'jsonapi')->willReturn('name')->shouldBeCalledTimes(1);

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
