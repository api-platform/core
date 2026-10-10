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
 * Shared selection rules for subscription identity, registration and publication.
 *
 * @internal
 */
trait SubscriptionFieldSelectionTrait
{
    private function normalizeFieldSelection(array $fields): array
    {
        unset($fields['mercureUrl'], $fields['clientSubscriptionId']);

        return $this->removeTypename($fields);
    }

    private function removeTypename(array $data): array
    {
        foreach ($data as $key => $value) {
            if ('__typename' === $key) {
                unset($data[$key]);
            } elseif (\is_array($value)) {
                $data[$key] = $this->removeTypename($value);
            }
        }

        return $data;
    }
}
