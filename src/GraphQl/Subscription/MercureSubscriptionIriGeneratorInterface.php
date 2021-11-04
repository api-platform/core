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

/**
 * Generates Mercure-related IRIs from a subscription ID.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface MercureSubscriptionIriGeneratorInterface
{
    public function generateTopicIri(string $subscriptionId): string;

    public function generateMercureUrl(string $subscriptionId, ?string $hub = null): string;
}
