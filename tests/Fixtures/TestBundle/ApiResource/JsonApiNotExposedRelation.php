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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operation;

/**
 * A resource with no public item operation — only a NotExposed GET.
 * Also exposed as a subresource of JsonApiDummy to verify that links.self
 * in entity identifier mode uses the NotExposed IRI, not the subresource URI.
 */
#[NotExposed(
    uriTemplate: '/jsonapi_not_exposed_relations/{id}',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    provider: [self::class, 'provide'],
)]
#[Get(
    uriTemplate: '/jsonapi_dummies/{dummyId}/not_exposed_relation',
    uriVariables: [
        'dummyId' => new Link(fromClass: JsonApiDummy::class, fromProperty: 'notExposedRelation'),
    ],
    formats: ['jsonapi' => ['application/vnd.api+json']],
    provider: [self::class, 'provide'],
)]
class JsonApiNotExposedRelation
{
    public function __construct(
        public int $id = 0,
        public string $label = '',
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (isset($uriVariables['dummyId'])) {
            // Subresource: resolve from parent dummy
            $dummy = JsonApiDummy::provide($operation, ['id' => $uriVariables['dummyId']], $context);

            return $dummy->notExposedRelation ?? new self();
        }

        $id = (int) ($uriVariables['id'] ?? 0);

        return new self(id: $id, label: 'NotExposed #'.$id);
    }
}
