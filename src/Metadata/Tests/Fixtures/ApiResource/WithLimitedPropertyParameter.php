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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;

#[
    ApiResource(operations: [
        new GetCollection(name: 'collection', parameters: [':property' => new QueryParameter(properties: ['name'])]),
    ])
]
class WithLimitedPropertyParameter
{
    public $id;
    public $name;
    public $description;
}
