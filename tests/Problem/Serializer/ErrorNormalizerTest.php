<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Problem\Serializer;

use ApiPlatform\Core\Problem\Serializer\ErrorNormalizer;
use Symfony\Component\Debug\Exception\FlattenException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ErrorNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportNormalization()
    {
        $normalizer = new ErrorNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new \Exception(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new \Exception(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));

        $this->assertTrue($normalizer->supportsNormalization(new FlattenException(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new FlattenException(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $normalizer = new ErrorNormalizer();

        $this->assertEquals(
            [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => 'Hello',
            ],
            $normalizer->normalize(new \Exception('Hello'))
        );
        $this->assertEquals(
            [
                'type' => 'https://dunglas.fr',
                'title' => 'Hi',
                'detail' => 'Hello',
            ],
            $normalizer->normalize(new \Exception('Hello'), null, ['type' => 'https://dunglas.fr', 'title' => 'Hi'])
        );
    }
}
