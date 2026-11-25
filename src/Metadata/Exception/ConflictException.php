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

namespace ApiPlatform\Metadata\Exception;

/**
 * Framework-agnostic 409 Conflict, mapped by both the Symfony and Laravel error handlers.
 */
class ConflictException extends \RuntimeException implements ExceptionInterface, HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return 409;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }
}
