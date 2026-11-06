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
     * @param list<ResponseInterface> $responses
     */
    private function assertPurgeSucceeded(array $responses): void
    {
        $failures = [];
        foreach ($responses as $response) {
            try {
                $response->getHeaders();
            } catch (ExceptionInterface $e) {
                [$headerName, $headerValue] = $response->getInfo('user_data');
                $failures[] = new PurgeFailure($response->getInfo('url'), $headerName, $headerValue, $e);
            }
        }

        if ($failures) {
            throw new PurgeFailedException($failures, \count($responses));
        }
    }
}
