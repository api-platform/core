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

namespace Workbench\App\ApiResource;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Workbench\App\Models\ProductOrder as ProductOrderModel;

#[ApiResource(
    operations: [new GetCollection()],
    stateOptions: new Options(modelClass: ProductOrderModel::class),
)]
#[QueryParameter(key: 'quantity', property: 'quantity', filter: EqualsFilter::class)]
#[QueryParameter(key: 'product.name', property: 'product.name', filter: EqualsFilter::class)]
#[QueryParameter(key: 'product.productVariations.variantName', property: 'product.productVariations.variantName', filter: EqualsFilter::class)]
#[QueryParameter(key: 'product.productVariations.skuCode', property: 'product.productVariations.skuCode', filter: EqualsFilter::class)]
class ProductOrder
{
    #[ApiProperty(readable: false, writable: false, identifier: true)]
    public ?int $id = null;

    #[ApiProperty(readable: false, writable: false)]
    public ?Product $product = null;

    public ?int $quantity = null;

    public ?string $customerName = null;
}
