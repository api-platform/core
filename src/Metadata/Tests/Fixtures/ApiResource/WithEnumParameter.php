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
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Tests\Fixtures\Enum\IntBackedPriority;
use ApiPlatform\Metadata\Tests\Fixtures\Enum\StringBackedStatus;

#[ApiResource(operations: [
    new GetCollection(name: 'collection', parameters: [
        'status' => new QueryParameter(enumClass: StringBackedStatus::class),
        'priority' => new QueryParameter(enumClass: IntBackedPriority::class),
        'priorityNoCast' => new QueryParameter(enumClass: IntBackedPriority::class, castToNativeType: false),
        'narrowed' => new QueryParameter(schema: ['enum' => ['draft']], enumClass: StringBackedStatus::class),
        'typed' => new QueryParameter(schema: ['type' => 'string'], enumClass: IntBackedPriority::class),
        'X-Status' => new HeaderParameter(enumClass: StringBackedStatus::class),
    ]),
])]
class WithEnumParameter
{
    public $id;
}
