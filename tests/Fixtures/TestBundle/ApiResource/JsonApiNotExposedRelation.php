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

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operation;

/**
 * A resource with no public item operation — only a NotExposed GET.
 * In JSON:API entity identifier mode, relations pointing to this resource
 * are resolved via IriConverter using the NotExposed operation.
 */
#[NotExposed(
    uriTemplate: '/jsonapi_not_exposed_relations/{id}',
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
        $id = (int) ($uriVariables['id'] ?? 0);

        return new self(id: $id, label: 'NotExposed #'.$id);
    }
}
