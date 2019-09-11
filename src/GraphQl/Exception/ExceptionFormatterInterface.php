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
interface ExceptionFormatterInterface
{
    /**
     * Formats the exception and returns the formatted array.
     */
    public function format(Error $error): array;

    /**
     * Check the exception, return true if you can format the exception.
     */
    public function supports(\Throwable $exception): bool;

    /**
     * Priority of your formatter in container. Higher number will be called sooner.
     */
    public function getPriority(): int;
}
