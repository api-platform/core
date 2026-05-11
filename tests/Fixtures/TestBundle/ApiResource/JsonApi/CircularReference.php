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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonApiCircularReference',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Get(
            uriTemplate: '/jsonapi_circular_references/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class CircularReference
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public ?CircularReference $parent = null;

    /** @var CircularReference[] */
    public array $children = [];

    public function __construct(int $id = 1)
    {
        $this->id = $id;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $first = new self(1);
        $second = new self(2);

        $first->parent = $first;
        $second->parent = $first;
        $first->children = [$first, $second];

        $id = (int) ($uriVariables['id'] ?? 1);

        return 2 === $id ? $second : $first;
    }
}
