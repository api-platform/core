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

#[ApiResource(shortName: 'SharedIriCategoryProjection', operations: [
    new Get(uriTemplate: '/shared_iri/categories/{id}', routeName: CategoryResource::ITEM_ROUTE_NAME, openapi: false, provider: [self::class, 'provide']),
])]
final class CategoryProjection
{
    public function __construct(public int $id = 0, public string $label = '')
    {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self((int) $uriVariables['id'], 'Category '.$uriVariables['id']);
    }
}
