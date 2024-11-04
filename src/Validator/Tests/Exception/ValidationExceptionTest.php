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

namespace ApiPlatform\Validator\Tests\Exception;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Validator\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidationExceptionTest extends TestCase
{
    public function testToString(): void
    {
        $e = new ValidationException(new ConstraintViolationList([
            new ConstraintViolation('message 1', '', [], '', '', 'invalid'),
            new ConstraintViolation('message 2', '', [], '', 'foo', 'invalid'),
        ]));
        $this->assertInstanceOf(RuntimeException::class, $e);
        $this->assertInstanceOf(\RuntimeException::class, $e);

        $this->assertSame(str_replace(\PHP_EOL, "\n", <<<TXT
message 1
foo: message 2
TXT
        ), $e->__toString());
    }

    public function testWithPrevious(): void
    {
        $previous = new \Exception();
        $e = new ValidationException(new ConstraintViolationList([
            new ConstraintViolation('message 1', '', [], '', '', 'invalid'),
        ]), null, $previous);
        $this->assertInstanceOf(\RuntimeException::class, $e);

        $this->assertSame(str_replace(\PHP_EOL, "\n", <<<TXT
message 1
TXT
        ), $e->__toString());
        $this->assertSame($previous, $e->getPrevious());
    }
}
