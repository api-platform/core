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

use ApiPlatform\Metadata\GraphQl\Operation;

/**
 * Manages all the queried subscriptions and creates their ID.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface OperationAwareSubscriptionManagerInterface extends SubscriptionManagerInterface
{
    public function retrieveSubscriptionId(array $context, ?array $result, ?Operation $operation = null): ?string;
}
