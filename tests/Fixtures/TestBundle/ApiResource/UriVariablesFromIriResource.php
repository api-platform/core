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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[Get(uriTemplate: '/uri_variables_from_iri_resources/{id}', provider: [self::class, 'provide'])]
#[GetCollection(uriTemplate: '/uri_variables_from_iri_resources', provider: [self::class, 'provide'])]
class UriVariablesFromIriResource
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public static function provide(Operation $operation, array $uriVariables = []): never
    {
        throw new \LogicException('The state provider must not be called to read the URI variables of an IRI.');
    }
}
