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
        new Query(resolver: WidgetResolver::class, read: false, name: 'get'),
    ],
)]
class Widget
{
    public ?string $id = null;

    public WidgetStatus $status = WidgetStatus::DRAFT;
}
