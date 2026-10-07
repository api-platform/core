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
 * A prepared payload whose state is acknowledged only after successful delivery.
 *
 * @internal
 */
final readonly class SubscriptionUpdate
{
    public function __construct(public RegisteredSubscription $subscription, public array $data, public ?string $fingerprint)
    {
    }

    public function getId(): string
    {
        return $this->subscription->id;
    }
}
