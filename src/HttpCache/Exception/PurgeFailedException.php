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

use ApiPlatform\Metadata\Exception\RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;

/**
 * Thrown once every purge request has been attempted, when at least one of them failed.
 *
 * It implements HttpClient's ExceptionInterface so that code catching the exception
 * the HTTP client used to throw directly still catches it.
 */
final class PurgeFailedException extends RuntimeException implements HttpClientExceptionInterface
{
    /**
     * @param non-empty-list<PurgeFailure> $failures
     */
    public function __construct(private readonly array $failures, int $requestCount)
    {
        parent::__construct(\sprintf('%d of %d HTTP cache purge requests failed.', \count($failures), $requestCount), 0, $failures[0]->getException());
    }

    /**
     * @return non-empty-list<PurgeFailure>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }
}
