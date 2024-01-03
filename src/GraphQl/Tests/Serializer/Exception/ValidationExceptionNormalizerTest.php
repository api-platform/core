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

use ApiPlatform\GraphQl\Serializer\Exception\ValidationExceptionNormalizer;
use ApiPlatform\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @author Mahmood Bazdar<mahmood@bazdar.me>
 */
class ValidationExceptionNormalizerTest extends TestCase
{
    private ValidationExceptionNormalizer $validationExceptionNormalizer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->validationExceptionNormalizer = new ValidationExceptionNormalizer();
    }

    public function testNormalize(): void
    {
        $exceptionMessage = 'exception message';
        $exception = new class($exceptionMessage) extends \Exception implements ConstraintViolationListAwareExceptionInterface {
            public function getConstraintViolationList(): ConstraintViolationListInterface
            {
                return new ConstraintViolationList([
                    new ConstraintViolation('message 1', '', [], '', 'field 1', 'invalid'),
                    new ConstraintViolation('message 2', '', [], '', 'field 2', 'invalid'),
                ]);
            }
        };
        $error = new Error('test message', null, null, [], null, $exception);

        $normalizedError = $this->validationExceptionNormalizer->normalize($error);
        $this->assertSame($exceptionMessage, $normalizedError['message']);
        $this->assertSame(422, $normalizedError['extensions']['status']);
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_INTERNAL')) {
            $this->assertSame('user', $normalizedError['extensions']['category']);
        }
        $this->assertArrayHasKey('violations', $normalizedError['extensions']);
        $this->assertEquals([
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
        $exception = $this->createStub(ConstraintViolationListAwareExceptionInterface::class);
        $exception->method('getConstraintViolationList')->willReturn(new ConstraintViolationList([]));
        $error = new Error('test message', null, null, [], null, $exception);

        $this->assertTrue($this->validationExceptionNormalizer->supportsNormalization($error));
    }
}
