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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\State\JsonApiErrorTestProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/jsonapi_error_test/{id}',
            provider: JsonApiErrorTestProvider::class,
        ),
    ],
    formats: ['jsonapi' => ['application/vnd.api+json']],
)]
class JsonApiErrorTestResource
{
    #[ApiProperty(identifier: true)]
    public string $id;

    public string $name;
}
