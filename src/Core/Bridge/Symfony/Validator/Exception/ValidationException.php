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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\Exception;

class_exists(\ApiPlatform\Symfony\Validator\Exception\ValidationException::class);

if (false) {
    final class ValidationException extends \ApiPlatform\Symfony\Validator\Exception\ValidationException
    {
    }
}
