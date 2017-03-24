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
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ConstraintViolationNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportNormalization()
    {
        $normalizer = new ConstraintViolationListNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new ConstraintViolationList(), ConstraintViolationListNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new ConstraintViolationList(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ConstraintViolationListNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $normalizer = new ConstraintViolationListNormalizer();

        $list = new ConstraintViolationList([
            new ConstraintViolation('a', 'b', [], 'c', 'd', 'e'),
            new ConstraintViolation('1', '2', [], '3', '4', '5'),
        ]);

        $expected = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => 'd: a
4: 1',
            'violations' => [
                    [
                        'propertyPath' => 'd',
                        'message' => 'a',
                    ],
                    [
                        'propertyPath' => '4',
                        'message' => '1',
                    ],
                ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($list));
    }
}
