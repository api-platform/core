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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EnumClassParameter;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/enum_class_parameters',
            parameters: [
                'status' => new QueryParameter(enumClass: Status::class),
                'priority' => new QueryParameter(enumClass: Priority::class),
            ],
            provider: [self::class, 'provide'],
        ),
    ]
)]
class EnumClassParameter
{
    public static function provide(Operation $operation): array
    {
        return [];
    }
}
