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

use ApiPlatform\Laravel\Eloquent\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Workbench\App\Models\Product as ProductModel;

#[ApiResource(
    shortName: 'Product',
    stateOptions: new Options(modelClass: ProductModel::class),
)]
#[Map(source: ProductModel::class)]
class Product
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?string $name = null;

    public ?float $price = null;
}
