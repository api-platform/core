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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: 'with_disabled_parameter_validation_on_resource{._format}',
            parameters: ['bla' => new QueryParameter(required: true)],
            provider: [self::class, 'collectionProvider'],
        ),
    ],
    queryParameterValidationEnabled: false,
)]
class WithDisabledParameterValidationOnResource
{
    public int $id = 1;

    public static function collectionProvider(): array
    {
        return [new self()];
    }
}
