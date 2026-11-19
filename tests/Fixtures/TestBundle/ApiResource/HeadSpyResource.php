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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\State\SpyPaginator;

#[ApiResource(
    shortName: 'HeadSpyResource',
    operations: [
        new GetCollection(
            uriTemplate: '/head_spy_resources',
            provider: [self::class, 'provide'],
        ),
        new GetCollection(
            uriTemplate: '/head_spy_stream_resources',
            provider: [self::class, 'provide'],
            jsonStream: true,
        ),
    ],
)]
final class HeadSpyResource
{
    public string $id = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): SpyPaginator
    {
        return new SpyPaginator();
    }
}
