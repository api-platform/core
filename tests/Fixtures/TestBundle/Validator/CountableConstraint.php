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

/**
 * A constraint that counts how many times its validator is invoked.
 * Used to verify that validation is not run more than once per request.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class CountableConstraint extends Constraint
{
}
