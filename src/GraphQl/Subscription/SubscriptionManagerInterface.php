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
     * Each publication pairs a subscription operation with its resource object.
     * Yield the supplied operation instance with each update to preserve resolved delivery options.
     * For delete events, the object is a snapshot with resourceClass, id (relative IRI),
     * iri (absolute IRI), type (string or list of strings), and private (field/value map).
     * Other events receive the resource object.
     *
     * @param list<array{object: object, operation: Subscription}> $publications Publications for one changed resource
     *
     * @return iterable<array{Subscription, SubscriptionUpdate}>
     */
    public function getUpdates(array $publications, string $type = 'update'): iterable;

    /** Records successful publication or dispatch of the prepared update. */
    public function acknowledge(SubscriptionUpdate $update): void;
}
