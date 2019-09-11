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

namespace ApiPlatform\Core\Tests\GraphQl\Exception\Formatter;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\GraphQl\Exception\Formatter\ValidationExceptionFormatter;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Mahmood Bazdar<mahmood@bazdar.me>
 */
class ValidationExceptionFormatterTest extends TestCase
{
    public function testFormat()
    {
        $exception = new ValidationException(new ConstraintViolationList([
            new ConstraintViolation('message 1', '', [], '', 'Field 1', 'invalid'),
            new ConstraintViolation('message 2', '', [], '', 'Field 2', 'invalid'),
        ]));
        $error = new Error('test message', null, null, null, null, $exception);
        $formatter = new ValidationExceptionFormatter();

        $formattedError = $formatter->format($error);
        $this->assertArrayHasKey('violations', $formattedError);
        $this->assertEquals(400, $formattedError['status']);
        $this->assertEquals([
            [
                'path' => 'Field 1',
                'message' => 'message 1',
            ],
            [
                'path' => 'Field 2',
                'message' => 'message 2',
            ],
        ], $formattedError['violations']);
        $this->assertEquals(Error::CATEGORY_GRAPHQL, $formattedError['extensions']['category']);
    }

    public function testSupports()
    {
        $exception = new ValidationException(new ConstraintViolationList([
            new ConstraintViolation('message 1', '', [], '', 'Field 1', 'invalid'),
        ]));
        $formatter = new ValidationExceptionFormatter();

        $this->assertTrue($formatter->supports($exception));
    }
}
