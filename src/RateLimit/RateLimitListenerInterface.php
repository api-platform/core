<?php
declare(strict_types=1);

namespace ApiPlatform\Core\RateLimit;

use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Interface for RateLimiter service
 *
 * @author Toby Griffiths <toby@cubicmushroom.co.uk>
 */
interface RateLimitListenerInterface
{
    /**
     * Tests the current request against each of the configured rate limiter configurations.
     *
     * @param GetResponseEvent $event
     *
     * @throws RuntimeException if no limiters are configured
     */
    public function onKernelRequest(GetResponseEvent $event): void;

}
