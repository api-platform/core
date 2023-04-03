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

use ApiPlatform\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class RuntimeExceptionNormalizerTest extends TestCase
{
    private RuntimeExceptionNormalizer $runtimeExceptionNormalizer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->runtimeExceptionNormalizer = new RuntimeExceptionNormalizer();
    }

    public function testNormalize(): void
    {
        $exceptionMessage = 'exception message';
        $exception = new \RuntimeException($exceptionMessage);
        $error = new Error('test message', null, null, [], null, $exception);

        $normalizedError = $this->runtimeExceptionNormalizer->normalize($error);
        $this->assertSame($exceptionMessage, $normalizedError['message']);
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_INTERNAL')) {
            $this->assertSame(Error::CATEGORY_INTERNAL, $normalizedError['extensions']['category']);
        }
    }

    public function testSupportsNormalization(): void
    {
        $exception = new \RuntimeException();
        $error = new Error('test message', null, null, [], null, $exception);

        $this->assertTrue($this->runtimeExceptionNormalizer->supportsNormalization($error));
    }
}
