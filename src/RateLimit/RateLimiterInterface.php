<?php
declare(strict_types=1);

namespace ApiPlatform\Core\RateLimit;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for config objects for rate limiting
 *
 * @author Toby Griffiths <toby@cubicmushroom.co.uk>
 */
interface RateLimiterInterface
{
    /**
     * Returns the key used when configuring the rate limiter.
     *
     * @return string
     */
    public static function getConfigKey(): string;


    /**
     * Returns the key to use when comparing requests.
     *
     * All sensitive info should be encrypted, so it's not visible.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getRateKey(Request $request): string;
}
