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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\ProductFactory;

class ObjectMapperTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    public function testObjectMapperMapsModelToApiResource(): void
    {
        /** @var \Workbench\App\Models\Product $product */
        $product = ProductFactory::new(['name' => 'Test Product', 'price' => 19.99])->create();

        $response = $this->get('/api/products/'.$product->id, ['Accept' => ['application/ld+json']]);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertEquals('Test Product', $data['name']);
        $this->assertEquals(19.99, $data['price']);
    }

    public function testObjectMapperMapsCollectionOfModels(): void
    {
        ProductFactory::new(['name' => 'Product 1', 'price' => 10.00])->create();
        ProductFactory::new(['name' => 'Product 2', 'price' => 20.00])->create();

        $response = $this->get('/api/products', ['Accept' => ['application/ld+json']]);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('member', $data);
        $this->assertCount(2, $data['member']);
        $this->assertEquals('Product 1', $data['member'][0]['name']);
        $this->assertEquals('Product 2', $data['member'][1]['name']);
    }
}
