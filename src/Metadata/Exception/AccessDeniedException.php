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

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AccessDeniedException extends AccessDeniedHttpException implements HttpExceptionInterface, ProblemExceptionInterface
{
    public function __construct(string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [], private readonly ?string $detail = null)
    {
        parent::__construct($message, $previous, $code, $headers);
    }

    public function getType(): string
    {
        return '/errors/403';
    }

    public function getTitle(): string
    {
        return 'An error occurred';
    }

    public function getStatus(): int
    {
        return 403;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getInstance(): ?string
    {
        return null;
    }

    public function getStatusCode(): int
    {
        return 403;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
