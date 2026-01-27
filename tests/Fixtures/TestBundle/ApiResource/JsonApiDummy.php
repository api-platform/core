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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[Get(
    uriTemplate: '/jsonapi_dummies/{id}',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    provider: [self::class, 'provide'],
)]
#[GetCollection(
    uriTemplate: '/jsonapi_dummies',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    provider: [self::class, 'provideCollection'],
)]
class JsonApiDummy
{
    public function __construct(
        public int $id = 0,
        public string $name = '',
        public ?JsonApiRelatedDummy $relatedDummy = null,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $id = (int) ($uriVariables['id'] ?? 0);

        return new self(
            id: $id,
            name: 'Dummy #'.$id,
            relatedDummy: new JsonApiRelatedDummy(id: 1, title: 'Related #1'),
        );
    }

    /**
     * @return list<self>
     */
    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [
            new self(id: 1, name: 'Dummy #1', relatedDummy: new JsonApiRelatedDummy(id: 1, title: 'Related #1')),
            new self(id: 2, name: 'Dummy #2'),
        ];
    }
}
