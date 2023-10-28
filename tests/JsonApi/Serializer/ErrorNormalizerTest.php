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

use ApiPlatform\JsonApi\Serializer\ErrorNormalizer;
use ApiPlatform\Tests\Mock\Exception\ErrorCodeSerializable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ErrorNormalizerTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testSupportsNormalization(): void
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
     * @dataProvider errorProvider
     *
     * @group legacy
     *
     * @param int    $status          http status code of the Exception
     * @param string $originalMessage original message of the Exception
     * @param bool   $debug           simulates kernel debug variable
     */
    public function testNormalize(int $status, string $originalMessage, bool $debug): void
    {
        $normalizer = new ErrorNormalizer($debug);
        $exception = FlattenException::create(new \Exception($originalMessage), $status);

        $expected = [
            'title' => 'An error occurred',
            'description' => ($debug || $status < 500) ? $originalMessage : Response::$statusTexts[$status],
        ];

        if ($debug) {
            $expected['trace'] = $exception->getTrace();
        }

        $this->assertSame($expected, $normalizer->normalize($exception, ErrorNormalizer::FORMAT, ['statusCode' => $status]));
    }

    /**
     * @group legacy
     */
    public function testNormalizeAnExceptionWithCustomErrorCode(): void
    {
        $status = Response::HTTP_BAD_REQUEST;
        $originalMessage = 'my-message';
        $debug = false;

        $normalizer = new ErrorNormalizer($debug);
        $exception = new ErrorCodeSerializable($originalMessage);

        $expected = [
            'title' => 'An error occurred',
            'description' => 'my-message',
            'code' => ErrorCodeSerializable::getErrorCode(),
        ];

        $this->assertSame($expected, $normalizer->normalize($exception, ErrorNormalizer::FORMAT, ['statusCode' => $status]));
    }

    /**
     * @group legacy
     */
    public function testNormalizeAFlattenExceptionWithCustomErrorCode(): void
    {
        $status = Response::HTTP_BAD_REQUEST;
        $originalMessage = 'my-message';
        $debug = false;

        $normalizer = new ErrorNormalizer($debug);
        $exception = FlattenException::create(new ErrorCodeSerializable($originalMessage), $status);

        $expected = [
            'title' => 'An error occurred',
            'description' => 'my-message',
            'code' => ErrorCodeSerializable::getErrorCode(),
        ];

        $this->assertSame($expected, $normalizer->normalize($exception, ErrorNormalizer::FORMAT, ['statusCode' => $status]));
    }

    public static function errorProvider(): array
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
}
