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

use ApiPlatform\Metadata\GraphQl\Subscription;

/**
 * Provides payloads for one subscription operation, independently of HTTP publication.
 */
interface SubscriptionPayloadProviderInterface extends SubscriptionManagerInterface
{
    /** @return list<array{string, array}> */
    public function getPushPayloadsForOperation(object $object, Subscription $operation, string $type = 'update'): array;
}
