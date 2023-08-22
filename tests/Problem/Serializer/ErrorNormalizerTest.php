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

namespace ApiPlatform\Tests\Problem\Serializer;

use ApiPlatform\Problem\Serializer\ErrorNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ErrorNormalizerTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testSupportNormalization(): void
    {
        $normalizer = new ErrorNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new \Exception(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new \Exception(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));

        $this->assertTrue($normalizer->supportsNormalization(new FlattenException(), ErrorNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new FlattenException(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorNormalizer::FORMAT));
        $this->assertEmpty($normalizer->getSupportedTypes('json'));
        $this->assertSame([
            \Exception::class => false,
            FlattenException::class => false,
        ], $normalizer->getSupportedTypes($normalizer::FORMAT));

        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->assertFalse($normalizer->hasCacheableSupportsMethod());
        }
    }

    /**
     * @group legacy
     */
    public function testNormalize(): void
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
     * @group legacy
     *
     * @param int    $status          http status code of the Exception
     * @param string $originalMessage original message of the Exception
     * @param bool   $debug           simulates kernel debug variable
     */
    public function testErrorServerNormalize(int $status, string $originalMessage, bool $debug): void
    {
        $normalizer = new ErrorNormalizer($debug);
        $exception = FlattenException::create(new \Exception($originalMessage), $status);

        $expected = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => ($debug || $status < 500) ? $originalMessage : Response::$statusTexts[$status] ?? Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
        ];

        if ($debug) {
            $expected['trace'] = $exception->getTrace();
        }

        $this->assertSame($expected, $normalizer->normalize($exception, null, ['statusCode' => $status]));
    }

    /**
     * @group legacy
     */
    public static function providerStatusCode(): \Iterator
    {
        yield [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', false];
        yield [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', false];
        yield [Response::HTTP_BAD_REQUEST, 'Bad Request Message', false];
        yield [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', true];
        yield [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', true];
        yield [Response::HTTP_BAD_REQUEST, 'Bad Request Message', true];
        yield [509, Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR], true];
    }

    /**
     * @group legacy
     */
    public function testErrorServerNormalizeContextStatus(): void
    {
        $normalizer = new ErrorNormalizer(false);
        $exception = FlattenException::create(new \Exception(''), 500);

        $expected = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => Response::$statusTexts[502],
        ];

        $this->assertSame($expected, $normalizer->normalize($exception, null, ['statusCode' => 502]));
    }
}
