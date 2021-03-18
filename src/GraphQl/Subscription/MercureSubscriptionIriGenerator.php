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

namespace ApiPlatform\Core\GraphQl\Subscription;

use Symfony\Bundle\MercureBundle\Mercure;
use Symfony\Component\Routing\RequestContext;

/**
 * Generates Mercure-related IRIs from a subscription ID.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MercureSubscriptionIriGenerator implements MercureSubscriptionIriGeneratorInterface
{
    private $requestContext;
    private $mercure;

    /**
     * @param Mercure|string $mercure
     */
    public function __construct(RequestContext $requestContext, $mercure)
    {
        if (\is_string($mercure)) {
            @trigger_error(sprintf('Passing a string as the second argument to "%s::__construct()" is deprecated, pass a "%s" instance instead.', __CLASS__, Mercure::class), \E_USER_DEPRECATED);
        }

        $this->requestContext = $requestContext;
        $this->mercure = $mercure;
    }

    public function generateTopicIri(string $subscriptionId): string
    {
        if ('' === $scheme = $this->requestContext->getScheme()) {
            $scheme = 'https';
        }
        if ('' === $host = $this->requestContext->getHost()) {
            $host = 'api-platform.com';
        }

        return "$scheme://$host/subscriptions/$subscriptionId";
    }

    public function generateMercureUrl(string $subscriptionId, ?string $hub = null): string
    {
        if (\is_string($this->mercure)) {
            return $this->mercure.'?topic='.$this->generateTopicIri($subscriptionId);
        }

        return $this->mercure->getHub($hub)->getUrl().'?topic='.$this->generateTopicIri($subscriptionId);
    }
}
