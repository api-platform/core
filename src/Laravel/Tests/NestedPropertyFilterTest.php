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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Product;
use Workbench\App\Models\ProductOrder;
use Workbench\App\Models\ProductVariation;

class NestedPropertyFilterTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('api-platform.name_converter', null);
            $config->set('api-platform.formats', ['jsonld' => ['application/ld+json']]);
        });
    }

    public function testFilterByNestedPropertyWithSnakeCase(): void
    {
        // Create test data
        $product1 = Product::create(['name' => 'Laptop', 'price' => 999.99]);
        $product2 = Product::create(['name' => 'Mouse', 'price' => 29.99]);

        ProductOrder::create([
            'product_id' => $product1->id,
            'quantity' => 2,
            'customer_name' => 'John Doe',
        ]);

        ProductOrder::create([
            'product_id' => $product2->id,
            'quantity' => 5,
            'customer_name' => 'Jane Smith',
        ]);

        // First test: simple property filter (no nesting)
        $response = $this->getJson('/api/product_orders?quantity=2', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'member');

        // Test filtering by nested product.name (snake_case in DB)
        $response = $this->getJson('/api/product_orders?product.name=Laptop', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'member');
        $response->assertJsonPath('member.0.quantity', 2);
    }

    public function testFilterByDeeplyNestedPropertyWithSnakeCase(): void
    {
        // Create test data
        $product1 = Product::create(['name' => 'Laptop', 'price' => 999.99]);
        $product2 = Product::create(['name' => 'Keyboard', 'price' => 79.99]);

        $variation1 = ProductVariation::create([
            'product_id' => $product1->id,
            'variant_name' => 'Gaming Edition',
            'sku_code' => 'LAP-GAMING-001',
            'price_adjustment' => 200.00,
        ]);

        $variation2 = ProductVariation::create([
            'product_id' => $product2->id,
            'variant_name' => 'Mechanical Blue',
            'sku_code' => 'KEY-MECH-BLUE',
            'price_adjustment' => 30.00,
        ]);

        ProductOrder::create([
            'product_id' => $product1->id,
            'quantity' => 1,
            'customer_name' => 'Gamer Pro',
        ]);

        ProductOrder::create([
            'product_id' => $product2->id,
            'quantity' => 3,
            'customer_name' => 'Office Supply',
        ]);

        // Test filtering by deeply nested product.productVariations.variantName
        // This property path has snake_case columns: product_variations.variant_name
        $response = $this->getJson('/api/product_orders?product.productVariations.variantName=Gaming%20Edition', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'member');
        $response->assertJsonPath('member.0.quantity', 1);
    }

    public function testFilterByDeeplyNestedPropertySkuCodeWithSnakeCase(): void
    {
        // Create test data
        $product = Product::create(['name' => 'Laptop', 'price' => 999.99]);

        ProductVariation::create([
            'product_id' => $product->id,
            'variant_name' => 'Standard Edition',
            'sku_code' => 'LAP-STD-001',
            'price_adjustment' => 0,
        ]);

        ProductVariation::create([
            'product_id' => $product->id,
            'variant_name' => 'Pro Edition',
            'sku_code' => 'LAP-PRO-001',
            'price_adjustment' => 500.00,
        ]);

        ProductOrder::create([
            'product_id' => $product->id,
            'quantity' => 2,
            'customer_name' => 'Corporate Buyer',
        ]);

        // Test filtering by deeply nested product.productVariations.skuCode
        // This tests: product_variations.sku_code (snake_case in DB)
        $response = $this->getJson('/api/product_orders?product.productVariations.skuCode=LAP-PRO-001', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'member');
        $response->assertJsonPath('member.0.quantity', 2);
    }

    public function testMultipleNestedFiltersWithSnakeCase(): void
    {
        // Create test data
        $laptop = Product::create(['name' => 'Laptop', 'price' => 999.99]);
        $mouse = Product::create(['name' => 'Mouse', 'price' => 29.99]);

        ProductVariation::create([
            'product_id' => $laptop->id,
            'variant_name' => 'Gaming',
            'sku_code' => 'LAP-GAME',
            'price_adjustment' => 200.00,
        ]);

        ProductVariation::create([
            'product_id' => $mouse->id,
            'variant_name' => 'Gaming',
            'sku_code' => 'MOU-GAME',
            'price_adjustment' => 10.00,
        ]);

        ProductOrder::create([
            'product_id' => $laptop->id,
            'quantity' => 1,
            'customer_name' => 'Laptop Buyer',
        ]);

        ProductOrder::create([
            'product_id' => $mouse->id,
            'quantity' => 2,
            'customer_name' => 'Mouse Buyer',
        ]);

        // Filter by both product.name AND product.productVariations.variantName
        $response = $this->getJson('/api/product_orders?product.name=Laptop&product.productVariations.variantName=Gaming', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'member');
        $response->assertJsonPath('member.0.quantity', 1);
    }
}
