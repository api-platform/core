<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\QueryParameter;

#[GetCollection(
    uriTemplate: 'with_security_parameters_collection{._format}',
    parameters: [
        'name' => new QueryParameter(security: 'is_granted("ROLE_ADMIN")'),
        'auth' => new HeaderParameter(security: '"secured" == auth'),
    ],
    provider: [self::class, 'collectionProvider'],
)]
class WithSecurityParameter
{
    public static function collectionProvider()
    {
        return [new self()];
    }
}
