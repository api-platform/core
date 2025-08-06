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

namespace ApiPlatform\JsonApi\Tests\Serializer;

use ApiPlatform\JsonApi\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\JsonApi\Tests\Fixtures\Dummy;
use ApiPlatform\JsonApi\Tests\Fixtures\RelatedDummy;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ConstraintViolationNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportNormalization(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $nameConverterInterface = $this->prophesize(NameConverterInterface::class);

        $normalizer = new ConstraintViolationListNormalizer($propertyMetadataFactoryProphecy->reveal(), $nameConverterInterface->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new ConstraintViolationList(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new ConstraintViolationList(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertEmpty($normalizer->getSupportedTypes('json'));
        $this->assertSame([ConstraintViolationListInterface::class => true], $normalizer->getSupportedTypes($normalizer::FORMAT));
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

    public function testNormalizeWithStringRoot(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        // Create a violation with a string root (simulating query parameter validation)
        $constraintViolationList = new ConstraintViolationList([
            new ConstraintViolation('Invalid page value.', 'Invalid page value.', [], 'page', 'page', 'invalid'),
        ]);

        $normalizer = new ConstraintViolationListNormalizer($propertyMetadataFactoryProphecy->reveal());

        $result = $normalizer->normalize($constraintViolationList);

        $this->assertEquals(
            [
                'errors' => [
                    [
                        'detail' => 'Invalid page value.',
                        'source' => [
                            'pointer' => 'data/attributes/page',
                        ],
                    ],
                ],
            ],
            $result
        );
    }

    public function testNormalizeWithNullRoot(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        // Create a violation with a null root
        $constraintViolationList = new ConstraintViolationList([
            new ConstraintViolation('Invalid value.', 'Invalid value.', [], null, 'field', 'invalid'),
        ]);

        $normalizer = new ConstraintViolationListNormalizer($propertyMetadataFactoryProphecy->reveal());

        // This should not throw a TypeError and should handle the null root gracefully
        $result = $normalizer->normalize($constraintViolationList);

        $this->assertEquals(
            [
                'errors' => [
                    [
                        'detail' => 'Invalid value.',
                        'source' => [
                            'pointer' => 'data/attributes/field',
                        ],
                    ],
                ],
            ],
            $result
        );
    }
}
