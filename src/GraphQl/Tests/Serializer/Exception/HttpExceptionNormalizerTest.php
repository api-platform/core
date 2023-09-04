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

namespace ApiPlatform\GraphQl\Tests\Serializer\Exception;

use ApiPlatform\GraphQl\Serializer\Exception\HttpExceptionNormalizer;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class HttpExceptionNormalizerTest extends TestCase
{
    private HttpExceptionNormalizer $httpExceptionNormalizer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->httpExceptionNormalizer = new HttpExceptionNormalizer();
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testNormalize(HttpException $exception, string $expectedExceptionMessage, int $expectedStatus, string $expectedCategory): void
    {
        $error = new Error('test message', null, null, [], null, $exception);

        $normalizedError = $this->httpExceptionNormalizer->normalize($error);
        $this->assertSame($expectedExceptionMessage, $normalizedError['message']);
        $this->assertSame($expectedStatus, $normalizedError['extensions']['status']);
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_INTERNAL')) {
            $this->assertSame($expectedCategory, $normalizedError['extensions']['category']);
        }
    }

    public static function exceptionProvider(): array
    {
        $exceptionMessage = 'exception message';

        return [
            'client error' => [new BadRequestHttpException($exceptionMessage), $exceptionMessage, 400, 'user'],
            'server error' => [new ServiceUnavailableHttpException(null, $exceptionMessage), $exceptionMessage, 503, 'internal'],
        ];
    }

    public function testSupportsNormalization(): void
    {
        $exception = new BadRequestHttpException();
        $error = new Error('test message', null, null, [], null, $exception);

        $this->assertTrue($this->httpExceptionNormalizer->supportsNormalization($error));
    }
}
