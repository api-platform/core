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
 * Generates an identifier used to identify a subscription.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubscriptionIdentifierGenerator implements SubscriptionIdentifierGeneratorInterface
{
    use SubscriptionFieldSelectionTrait;

    public function generateSubscriptionIdentifier(array $fields): string
    {
        return hash('sha256', print_r($this->normalizeFieldSelection($fields), true));
    }
}
