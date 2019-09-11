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

namespace ApiPlatform\Core\GraphQl\Exception;

use GraphQL\Error\Error;

/**
 * @expremintal
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
interface ExceptionFormatterCallbackInterface
{
    /**
     * Callback function will be used for formatting GraphQL errors.
     */
    public function __invoke(Error $error): array;
}
