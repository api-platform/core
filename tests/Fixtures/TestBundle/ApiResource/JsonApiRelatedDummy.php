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
use ApiPlatform\Metadata\Operation;

#[Get(
    uriTemplate: '/jsonapi_related_dummies/{id}',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    provider: [self::class, 'provide'],
)]
class JsonApiRelatedDummy
{
    public function __construct(
        public int $id = 0,
        public string $title = '',
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $id = (int) ($uriVariables['id'] ?? 0);

        return new self(id: $id, title: 'Related #'.$id);
    }
}
