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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Query;

#[ApiResource(
    shortName: 'GoldenQuery',
    operations: [
        new Query(
            uriTemplate: '/golden_query',
            name: 'golden_query',
            input: GoldenQueryCriteria::class,
            read: false,
            deserialize: true,
            write: true,
            processor: [self::class, 'process'],
        ),
    ],
)]
final class GoldenQueryResource
{
    public ?int $id = null;

    public string $title = '';

    public static function process(mixed $data): mixed
    {
        return $data;
    }
}
