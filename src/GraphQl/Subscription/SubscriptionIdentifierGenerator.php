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
    public function generateSubscriptionIdentifier(array $fields): string
    {
        unset($fields['mercureUrl'], $fields['clientSubscriptionId']);
        $fields = $this->removeTypename($fields);

        return hash('sha256', print_r($fields, true));
    }

    private function removeTypename(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($key === '__typename') {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->removeTypename($value);
            }
        }

        return $data;
    }
}
