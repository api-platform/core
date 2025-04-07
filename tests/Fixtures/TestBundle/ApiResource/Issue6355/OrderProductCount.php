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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6355;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\Issue6355\UpdateOrderProductCountController;

#[ApiResource(
    shortName: 'OrderProduct',
    operations: [
        new NotExposed(),
        new Patch(
            uriTemplate: '/order_products/{id}/count',
            controller: UpdateOrderProductCountController::class,
            class: OrderDto::class,
            input: OrderProductCount::class,
            output: OrderDto::class,
            read: false,
            write: false,
            name: 'order_product_update_count',
            uriVariables: ['id']
        ),
    ],
    order: ['position' => 'ASC'],
)]
class OrderProductCount
{
    #[ApiProperty(writable: false, identifier: true)]
    public ?int $id = null;
    public ?int $count = null;
}
