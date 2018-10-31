<?php
declare(strict_types=1);


namespace ApiPlatform\Core\RateLimit;

use ApiPlatform\Core\Exception\RateLimitExceededException;
use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use function apcu_add;
use function apcu_inc;

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
     * Map of characters used for time_frame to characters used to retrieve DateTimeImmutable part value
     *
     * @var string[]
     */
    private $timeFrameScaleCharacterToDateFormatCharacterMap = [
        'Y' => 'Y',
        'M' => 'm',
        'W' => 'W',
        'D' => 'j',
        'h' => 'H',
        'm' => 'i',
        's' => 's',
    ];


    /**
     * Injects dependencies and sets default values.
     */
    public function __construct(RequestStack $requestStack, array $config)
    {
        $this->requestStack = $requestStack;
        $this->config       = $config;
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
            $limiterConfig = $this->config[$rateLimiter::getConfigKey()];

            $rateKey       =
                $rateLimiter->getRateKey($request) . ':' . $this->getTimeFrameString($limiterConfig['time_frame']);
            $hashedRateKey = hash('sha512', $rateKey);
            apcu_add($hashedRateKey, 0);
            $count = apcu_inc($hashedRateKey);

            if (false === $count) {
                throw new RuntimeException('Unable to write rate limit to store');
            }

            if ($count > $limiterConfig['capacity']) {
                throw new RateLimitExceededException();
            }
        }
    }


    /**
     * Appends the current time frame string to the given rate key.
     *
     * @param array $timeFrame
     *
     * @return string
     */
    private function getTimeFrameString(array $timeFrameConfig): string
    {
        ['window' => $window, 'scale' => $scale] = $timeFrameConfig;

        $now = new \DateTimeImmutable();

        $timeFrameString = '';

        foreach ($this->timeFrameScaleCharacterToDateFormatCharacterMap as $timeFrameCharacter => $dateFormatCharacter)
        {
            $value = (int)$now->format($dateFormatCharacter);

            if ($timeFrameConfig['scale'] === $timeFrameCharacter) {
                return $timeFrameString . '_' . ($value - ($value % $window)) . $timeFrameCharacter;
            }

            $timeFrameString .= '_' . $value . $timeFrameCharacter;
        }

        throw new RuntimeException('Should never get here!');

    }
}
