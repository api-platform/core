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

namespace ApiPlatform\Core\Tests\GraphQl\Serializer\Exception;

use ApiPlatform\GraphQl\Serializer\Exception\ErrorNormalizer;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ErrorNormalizerTest extends TestCase
{
    private $errorNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorNormalizer = new ErrorNormalizer();
    }

    public function testNormalize(): void
    {
        $errorMessage = 'test message';
        $error = new Error($errorMessage);

        $normalizedError = $this->errorNormalizer->normalize($error);
        $this->assertSame($errorMessage, $normalizedError['message']);
        $this->assertSame(Error::CATEGORY_GRAPHQL, $normalizedError['extensions']['category']);
    }

    public function testSupportsNormalization(): void
    {
        $error = new Error('test message');

        $this->assertTrue($this->errorNormalizer->supportsNormalization($error));
    }
}
