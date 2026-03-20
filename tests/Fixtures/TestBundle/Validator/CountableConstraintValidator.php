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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator that counts invocations via a static counter.
 * Reset the counter before each test with CountableConstraintValidator::$count = 0.
 */
class CountableConstraintValidator extends ConstraintValidator
{
    public static int $count = 0;

    public function validate(mixed $value, Constraint $constraint): void
    {
        ++self::$count;
    }
}
