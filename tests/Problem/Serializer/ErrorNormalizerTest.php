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

use ApiPlatform\Core\Problem\Serializer\ErrorNormalizer;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @dataProvider providerStatusCode
     *
     * @param $status            http status code of the Exception
     * @param $originalMessage   original message of the Exception
     * @param $debug             simulates kernel debug variable
     */
    public function testErrorServerNormalize($status, $originalMessage, $debug)
    {
        $normalizer = new ErrorNormalizer($debug);
        $exception = FlattenException::create(new \Exception($originalMessage), $status);

        $expected = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => ($debug || $status < 500) ? $originalMessage : Response::$statusTexts[$status],
        ];

        if ($debug) {
            $expected['trace'] = $exception->getTrace();
        }

        $this->assertEquals($expected, $normalizer->normalize($exception, null, ['statusCode' => $status]));
    }

    public function providerStatusCode()
    {
        return [
            [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', false],
            [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', false],
            [Response::HTTP_BAD_REQUEST, 'Bad Request Message', false],
            [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', true],
            [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', true],
            [Response::HTTP_BAD_REQUEST, 'Bad Request Message', true],
        ];
    }

    public function testErrorServerNormalizeContextStatus()
    {
        $normalizer = new ErrorNormalizer(false);
        $exception = FlattenException::create(new \Exception(''), 500);

        $expected = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => Response::$statusTexts[502],
        ];

        $this->assertEquals($expected, $normalizer->normalize($exception, null, ['statusCode' => 502]));
    }
}
