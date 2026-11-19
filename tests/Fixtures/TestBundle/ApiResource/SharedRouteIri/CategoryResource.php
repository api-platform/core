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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedRouteIri;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(shortName: 'SharedIriCategory', operations: [
    new Get(uriTemplate: '/shared_iri/categories/{id}', provider: [self::class, 'provide']),
])]
final class CategoryResource
{
    public const ITEM_ROUTE_NAME = '_api_/shared_iri/categories/{id}_get';

    public function __construct(public int $id = 0, public string $name = '')
    {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self((int) $uriVariables['id'], 'Category '.$uriVariables['id']);
    }
}
