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

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Cart;
use Workbench\App\Models\CartItem;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'product_sku' => 'SKU-'.$this->faker->unique()->numberBetween(1000, 9999),
            'quantity' => $this->faker->numberBetween(1, 5),
            'price_at_addition' => $this->faker->randomFloat(2, 5, 200),
            'shopping_cart_id' => Cart::factory(),
        ];
    }
}
