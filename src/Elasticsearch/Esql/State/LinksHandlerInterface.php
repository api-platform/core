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

namespace ApiPlatform\Elasticsearch\Esql\State;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;

/**
 * Alters the ES|QL query according to the operation links (e.g. a subresource
 * URI template such as "/users/{userId}/books" adding `WHERE user_id == ?id`).
 *
 * Set an instance of this interface, or any callable, on the "handleLinks"
 * Elasticsearch state option to enable it. Service identifiers are not
 * supported: pass the instance itself.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
interface LinksHandlerInterface
{
    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context      the context contains the "operation" and the "resourceClass" keys
     */
    public function handleLinks(EsqlQuery $query, array $uriVariables, array $context): void;
}
