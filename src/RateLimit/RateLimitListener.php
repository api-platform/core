<?php
declare(strict_types=1);


namespace ApiPlatform\Core\RateLimit;

use function apcu_add;
use function apcu_inc;
use ApiPlatform\Core\Exception\RateLimitExceededException;
use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Service to rate limit requests
 *
 * @author Toby Griffiths <toby@cubicmushroom.co.uk>
 */
class RateLimitListener implements RateLimitListenerInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $config;

    /**
     * @var RateLimiterInterface[]
     */
    private $rateLimiters;


    /**
     * Injects dependencies and sets default values.
     */
    public function __construct(RequestStack $requestStack, array $config)
    {
        $this->requestStack = $requestStack;
        $this->config = $config;
        $this->rateLimiters = [];
    }


    /**
     * Adds a rate limit config.
     *
     * @param RateLimiterInterface $rateLimitConfig
     */
    public function addRateLimiters(RateLimiterInterface $rateLimitConfig): void
    {
        $this->rateLimiters[] = $rateLimitConfig;
    }


    /**
     * Tests the current request against each of the configured rate limiter configurations.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if ($this->requestStack->getMasterRequest() !== $request) {
            return;
        }

        foreach ($this->rateLimiters as $rateLimiter) {
            $rateKey = $rateLimiter->getRateKey($request);
            $hashedRateKey = hash('sha512', $rateKey);
            apcu_add($hashedRateKey, 0);
            $count = apcu_inc($hashedRateKey);

            if (false === $count) {
                throw new RuntimeException('Unable to write rate limit to store');
            }

            $limiterConfig = $this->config[$rateLimiter::getConfigKey()];

            if ($count > $limiterConfig['capacity']) {
                throw new RateLimitExceededException();
            }
        }
    }
}
