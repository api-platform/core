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

namespace ApiPlatform\HttpCache\Exception;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

final readonly class PurgeFailure
{
    public function __construct(
        private ?string $url,
        private string $headerName,
        private string $headerValue,
        private ExceptionInterface $exception,
    ) {
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function getHeaderValue(): string
    {
        return $this->headerValue;
    }

    public function getException(): ExceptionInterface
    {
        return $this->exception;
    }
}
