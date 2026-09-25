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

namespace ApiPlatform\HttpCache;

use ApiPlatform\HttpCache\Exception\PurgeFailedException;
use ApiPlatform\HttpCache\Exception\PurgeFailure;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Waits for purge requests that were all sent up front, so they run concurrently
 * and one failing request does not stop the others.
 *
 * @internal
 */
trait PurgeResponsesTrait
{
    /**
     * @param list<array{ResponseInterface, string, string}> $requests each response with the header name and value it was sent with
     *
     * @throws PurgeFailedException when at least one request failed
     */
    private function assertPurgeSucceeded(array $requests): void
    {
        $failures = [];
        foreach ($requests as [$response, $headerName, $headerValue]) {
            try {
                // getHeaders() throws on transport errors and on 3xx/4xx/5xx statuses, as the response destructor did
                $response->getHeaders();
            } catch (ExceptionInterface $e) {
                $failures[] = new PurgeFailure($response->getInfo('url'), $headerName, $headerValue, $e);
            }
        }

        if ($failures) {
            throw new PurgeFailedException($failures, \count($requests));
        }
    }
}
