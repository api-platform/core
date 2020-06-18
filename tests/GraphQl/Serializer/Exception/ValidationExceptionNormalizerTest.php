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

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\GraphQl\Serializer\Exception\ValidationExceptionNormalizer;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Mahmood Bazdar<mahmood@bazdar.me>
 */
class ValidationExceptionNormalizerTest extends TestCase
{
    private $validationExceptionNormalizer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validationExceptionNormalizer = new ValidationExceptionNormalizer();
    }

    public function testNormalize(): void
    {
        $exceptionMessage = 'exception message';
        $exception = new ValidationException(new ConstraintViolationList([
            new ConstraintViolation('message 1', '', [], '', 'field 1', 'invalid'),
            new ConstraintViolation('message 2', '', [], '', 'field 2', 'invalid'),
        ]), $exceptionMessage);
        $error = new Error('test message', null, null, null, null, $exception);

        $normalizedError = $this->validationExceptionNormalizer->normalize($error);
        $this->assertSame($exceptionMessage, $normalizedError['message']);
        $this->assertSame(400, $normalizedError['extensions']['status']);
        $this->assertSame('user', $normalizedError['extensions']['category']);
        $this->assertArrayHasKey('violations', $normalizedError['extensions']);
        $this->assertSame([
            [
                'path' => 'field 1',
                'message' => 'message 1',
            ],
            [
                'path' => 'field 2',
                'message' => 'message 2',
            ],
        ], $normalizedError['extensions']['violations']);
    }

    public function testSupportsNormalization(): void
    {
        $exception = new ValidationException(new ConstraintViolationList([]));
        $error = new Error('test message', null, null, null, null, $exception);

        $this->assertTrue($this->validationExceptionNormalizer->supportsNormalization($error));
    }
}
