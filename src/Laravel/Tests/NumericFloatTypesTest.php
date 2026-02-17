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

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

/**
 * Test for issue #7566: Laravel Eloquent POST operation for double column fails.
 *
 * This test verifies that float/double/decimal values can be posted as JSON numbers
 * (not strings) and are properly handled by the type metadata extractor.
 */
class NumericFloatTypesTest extends TestCase
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
            $config->set('api-platform.name_converter');
            $config->set('api-platform.formats', ['jsonld' => ['application/ld+json']]);
            $config->set('api-platform.docs_formats', ['jsonld' => ['application/ld+json']]);
        });
    }

    /**
     * Test that numeric (non-string) float values can be POSTed to decimal columns.
     *
     * Issue #7566: When posting numeric values like {"price": 1.99} instead of {"price": "1.99"},
     * the serializer would throw: "The type of the 'price' attribute must be 'string', 'double' given."
     *
     * This was caused by the type metadata extractor not recognizing 'float', 'decimal', or 'numeric'
     * types in EloquentPropertyMetadataFactory.
     */
    public function testPostNumericFloatValueToDecimalColumn(): void
    {
        $cartData = [
            'product_sku' => 'SKU_TEST_001',
            'quantity' => 2,
            'price_at_addition' => 19.99, // Numeric value, not string "19.99"
            'shopping_cart' => [
                'user_identifier' => 'user-'.Str::uuid()->toString(),
                'status' => 'active',
            ],
        ];

        $response = $this->postJson('/api/cart_items', $cartData, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);

        // Before the fix, this would fail with a 400 error and serialization type mismatch
        $response->assertStatus(201);

        $response
            ->assertJson([
                '@context' => '/api/contexts/CartItem',
                '@type' => 'CartItem',
                'product_sku' => 'SKU_TEST_001',
                'quantity' => 2,
                'price_at_addition' => 19.99,
            ]);
    }

    /**
     * Test that various numeric float values are handled correctly.
     */
    public function testPostVariousNumericFloatValues(): void
    {
        // Test with small decimal value
        $cartData1 = [
            'product_sku' => 'SKU_TEST_002',
            'quantity' => 1,
            'price_at_addition' => 1.99,
            'shopping_cart' => [
                'user_identifier' => 'user-'.Str::uuid()->toString(),
                'status' => 'active',
            ],
        ];

        $response1 = $this->postJson('/api/cart_items', $cartData1, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response1->assertStatus(201);

        // Test with large decimal value
        $cartData2 = [
            'product_sku' => 'SKU_TEST_003',
            'quantity' => 1,
            'price_at_addition' => 999999.99,
            'shopping_cart' => [
                'user_identifier' => 'user-'.Str::uuid()->toString(),
                'status' => 'active',
            ],
        ];

        $response2 = $this->postJson('/api/cart_items', $cartData2, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response2->assertStatus(201);

        // Test with zero
        $cartData3 = [
            'product_sku' => 'SKU_TEST_004',
            'quantity' => 1,
            'price_at_addition' => 0.0,
            'shopping_cart' => [
                'user_identifier' => 'user-'.Str::uuid()->toString(),
                'status' => 'active',
            ],
        ];

        $response3 = $this->postJson('/api/cart_items', $cartData3, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response3->assertStatus(201);
    }
}
