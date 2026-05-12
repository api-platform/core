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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7966;

use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource(
    operations: [],
    graphQlOperations: [
        new QueryCollection(
            provider: [self::class, 'provide'],
            paginationEnabled: false,
            parameters: [
                'order[:property]' => new QueryParameter(filter: new SortFilter()),
            ],
        ),
    ],
)]
final class SortFilterParameterDummy
{
    public ?string $id = null;
    public ?string $name = null;

    public static function provide(): array
    {
        return [];
    }
}
