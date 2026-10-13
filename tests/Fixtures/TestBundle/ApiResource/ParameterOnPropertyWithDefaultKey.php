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
    uriTemplate: '/parameter_on_property_with_default_key',
    operations: [new GetCollection()]
)]
class ParameterOnPropertyWithDefaultKey
{
    public string $id = '';

    #[QueryParameter(description: 'Search by title')]
    public string $title = '';
}
