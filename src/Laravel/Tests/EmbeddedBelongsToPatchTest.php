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

final class EmbeddedBelongsToPatchTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    public function testPatchPersistsModifiedEmbeddedBelongsToRelation(): void
    {
        $createCart = $this->postJson('/api/shopping_carts', [
            'userIdentifier' => 'user-original',
            'status' => 'active',
            'cartItems' => [
                [
                    'productSku' => 'SKU-PATCH-1',
                    'quantity' => 1,
                    'priceAtAddition' => 9.99,
                ],
            ],
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $createCart->assertStatus(201);

        $cartIri = $createCart->json()['@id'];
        $itemIri = $createCart->json()['cartItems'][0]['@id'];

        $response = $this->patchJson($itemIri, [
            'shoppingCart' => [
                'userIdentifier' => 'user-updated',
            ],
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/merge-patch+json']);
        $response->assertStatus(200);

        $check = $this->get($cartIri, ['Accept' => ['application/ld+json']]);
        $this->assertSame('user-updated', $check->json()['userIdentifier']);
    }
}
