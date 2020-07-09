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

namespace ApiPlatform\Core\GraphQl\Error;

/**
 *
 * A function which passes the errors thrown by the GraphQL library into the formatters
 *
 * @experimental
 *
 * @author Ollie Harridge <code@oll.ie>
 */
interface ErrorHandlerInterface
{
    public function __invoke(array $errors, callable $formatter): array;
}
