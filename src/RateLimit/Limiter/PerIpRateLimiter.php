<?php
declare(strict_types=1);

namespace ApiPlatform\Core\RateLimit\Limiter;

use ApiPlatform\Core\Exception\RateLimitExceededException;
use ApiPlatform\Core\RateLimit\RateLimiterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rate limit request from a given IP address
 *
 * @author Toby Griffiths <toby@cubicmushroom.co.uk>
 */
class PerIpRateLimiter implements RateLimiterInterface
{
    /**
     * Returns the key used when configuring the rate limiter.
     *
     * @return string
     */
    public static function getConfigKey(): string
    {
        return 'per_ip_address';
    }


    /**
     * @param Request $request
     *
     * @throws RateLimitExceededException if the user has exceeded their rate limit
     */
    public function getRateKey(Request $request): string
    {
        return $request->getClientIp();
    }


}
