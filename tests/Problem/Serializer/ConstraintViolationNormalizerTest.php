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

namespace ApiPlatform\Core\Tests\Problem\Serializer;

use ApiPlatform\Core\Problem\Serializer\ConstraintViolationListNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ConstraintViolationNormalizerTest extends TestCase
{
    public function testSupportNormalization()
    {
        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $normalizer = new ConstraintViolationListNormalizer([], $nameConverterProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new ConstraintViolationList(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new ConstraintViolationList(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $normalizer = new ConstraintViolationListNormalizer(['severity', 'anotherField1'], $nameConverterProphecy->reveal());

        $nameConverterProphecy->normalize(Argument::type('string'), null, Argument::type('string'))->will(function ($args) {
            return '_'.$args[0];
        });

        // Note : we use NotNull constraint and not Constraint class because Constraint is abstract
        $constraint = new NotNull();
        $constraint->payload = ['severity' => 'warning', 'anotherField2' => 'aValue'];
        $list = new ConstraintViolationList([
            new ConstraintViolation('a', 'b', [], 'c', 'd', 'e', null, null, $constraint),
            new ConstraintViolation('1', '2', [], '3', '4', '5'),
        ]);

        $expected = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => '_d: a
_4: 1',
            'violations' => [
                [
                    'propertyPath' => '_d',
                    'message' => 'a',
                    'payload' => [
                        'severity' => 'warning',
                    ],
                ],
                [
                    'propertyPath' => '_4',
                    'message' => '1',
                ],
            ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($list));
    }
}
