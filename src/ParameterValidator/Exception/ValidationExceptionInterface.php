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

namespace ApiPlatform\ParameterValidator\Exception;

use ApiPlatform\Metadata\Exception\ExceptionInterface;

/**
 * This Exception is thrown when any parameter validation fails.
 */
interface ValidationExceptionInterface extends ExceptionInterface, \Stringable
{
}
