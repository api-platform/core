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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(uriTemplate: '/shared_route_variable/alpha/{id}', name: 'shared_route_variable_alpha_get', routeName: 'shared_route_variable_item', openapi: false, provider: [self::class, 'provide'])]
final class VariableRouteAlpha
{
    public function __construct(public int $id = 0)
    {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self((int) $uriVariables['id']);
    }
}
