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

namespace ApiPlatform\GraphQl\Subscription;

use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Routing\RequestContext;

/**
 * Generates Mercure-related IRIs from a subscription ID.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MercureSubscriptionIriGenerator implements MercureSubscriptionIriGeneratorInterface
{
    public function __construct(private readonly RequestContext $requestContext, private readonly HubRegistry $registry)
    {
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
        return sprintf('%s?topic=%s', $this->registry->getHub($hub)->getUrl(), $this->generateTopicIri($subscriptionId));
    }
}
