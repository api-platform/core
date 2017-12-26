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
/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\Exception;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Validator\Exception\ValidationException as MainValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidationExceptionTest extends TestCase
{
    public function testToString()
    {
        $e = new ValidationException(new ConstraintViolationList([
            new ConstraintViolation('message 1', '', [], '', '', 'invalid'),
            new ConstraintViolation('message 2', '', [], '', 'foo', 'invalid'),
        ]));
        $this->assertInstanceOf(MainValidationException::class, $e);
        $this->assertInstanceOf(RuntimeException::class, $e);
        $this->assertInstanceOf(\RuntimeException::class, $e);

        $this->assertEquals(<<<TXT
message 1
foo: message 2
TXT
, $e->__toString());
    }
}
