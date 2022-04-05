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

namespace ApiPlatform\Core\Filter\Validator;

class_exists(\ApiPlatform\Api\QueryParameterValidator\Validator\MultipleOf::class);

if (false) {
    final class MultipleOf extends \ApiPlatform\Api\QueryParameterValidator\Validator\MultipleOf
    {
    }
}
