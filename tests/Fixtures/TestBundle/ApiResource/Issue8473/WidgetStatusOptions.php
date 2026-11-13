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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8473;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;

#[ApiResource(
    operations: [],
    graphQlOperations: [
        new Query(
            name: 'widgetStatusOptions',
            resolver: WidgetStatusOptionsResolver::class,
            args: ['status' => ['type' => 'WidgetStatus!']],
            read: false,
        ),
    ],
)]
class WidgetStatusOptions
{
    /** @var list<string> */
    public array $options = [];
}
