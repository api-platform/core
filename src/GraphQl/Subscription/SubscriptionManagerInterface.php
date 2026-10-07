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
 * Registers subscriptions, prepares updates, and acknowledges successful publication.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface SubscriptionManagerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function retrieveSubscriptionId(array $context, ?array $result, Subscription $operation): ?string;

    /**
     * Prepares updates for a create, update, or delete event without acknowledging publication.
     * For delete events, $object is a snapshot with resourceClass, id (relative IRI),
     * iri (absolute IRI), type (string or list of strings), and private (field/value map).
     * Other events receive the resource object.
     *
     * @return iterable<SubscriptionUpdate>
     */
    public function getUpdates(object $object, Subscription $operation, string $type = 'update'): iterable;

    /** Records successful publication or dispatch of the prepared update. */
    public function acknowledge(SubscriptionUpdate $update): void;
}
