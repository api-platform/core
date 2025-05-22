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

class SnakeCaseApiTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('api-platform.name_converter', null);
            $config->set('api-platform.formats', ['jsonld' => ['application/ld+json']]);
            $config->set('api-platform.docs_formats', ['jsonld' => ['application/ld+json']]);
        });
    }

    public function testRelationIsHandledOnCreateWithNestedDataSnakeCase(): void
    {
        $cartData = [
            'product_sku' => 'SKU_TEST_001',
            'quantity' => 2,
            'price_at_addition' => '19.99',
            'shopping_cart' => [
                'user_identifier' => 'user-'.Str::uuid()->toString(),
                'status' => 'active',
            ],
        ];

        $response = $this->postJson('/api/cart_items', $cartData, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(201);

        $response
            ->assertJson([
                '@context' => '/api/contexts/CartItem',
                '@id' => '/api/cart_items/1',
                '@type' => 'CartItem',
                'id' => 1,
                'product_sku' => 'SKU_TEST_001',
                'quantity' => 2,
                'price_at_addition' => 19.99,
                'shopping_cart' => [
                    '@id' => '/api/shopping_carts/1',
                    '@type' => 'ShoppingCart',
                    'user_identifier' => $cartData['shopping_cart']['user_identifier'],
                    'status' => 'active',
                ],
            ]);
    }

    public function testFailWithCamelCase(): void
    {
        $cartData = [
            'productSku' => 'SKU_TEST_001',
            'quantity' => 2,
            'priceAtAddition' => '19.99',
            'shoppingCart' => [
                'userIdentifier' => 'user-'.Str::uuid()->toString(),
                'status' => 'active',
            ],
        ];

        $response = $this->postJson('/api/cart_items', $cartData, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(422);
    }
}
