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

namespace ApiPlatform\Symfony\Security\Exception;

use ApiPlatform\Metadata\Exception\AccessDeniedException as MetadataAccessDeniedException;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

/**
 * @deprecated since API Platform 4.4, use {@see MetadataAccessDeniedException} instead
 */
final class AccessDeniedException extends ExceptionAccessDeniedException implements HttpExceptionInterface, ProblemExceptionInterface
{
    public function __construct(
        string $message = 'Access Denied.',
        ?\Throwable $previous = null,
        int $code = 403,
        bool $triggerDeprecation = true,
        private readonly ?string $detail = null,
    ) {
        if ($triggerDeprecation) {
            trigger_deprecation('api-platform/core', '4.4', 'The "%s" class is deprecated, use "%s" instead.', self::class, MetadataAccessDeniedException::class);
        }

        parent::__construct($message, $previous, $code);
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

    public function getDetail(): string
    {
        return $this->detail ?? $this->getMessage();
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
