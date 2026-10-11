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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Issue8664Item;

/**
 * @see https://github.com/api-platform/core/issues/8664
 */
final class Issue8664Test extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @see https://github.com/api-platform/core/issues/8664
     */
    public function testPostWithExplicitNullBelongsToDoesNotError(): void
    {
        $response = $this->postJson('/api/issue8664_items', [
            'code' => 'a',
            'category' => null,
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);

        $response->assertStatus(201);
        $this->assertNull(Issue8664Item::query()->findOrFail($response->json('id'))->category_id);
    }

    /**
     * @see https://github.com/api-platform/core/issues/8664
     */
    public function testPatchWithExplicitNullClearsBelongsTo(): void
    {
        $category = $this->postJson('/api/issue8664_categories', [
            'name' => 'tools',
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $category->assertStatus(201);

        $created = $this->postJson('/api/issue8664_items', [
            'code' => 'a',
            'category' => $category->json('@id'),
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $created->assertStatus(201);

        $item = Issue8664Item::query()->findOrFail($created->json('id'));
        $this->assertNotNull($item->category_id);

        $response = $this->patchJson($created->json('@id'), [
            'category' => null,
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/merge-patch+json']);

        $response->assertStatus(200);
        $this->assertNull($item->fresh()->category_id);
    }

    /**
     * @see https://github.com/api-platform/core/issues/8664
     */
    public function testOmittingBelongsToDoesNotClearIt(): void
    {
        $category = $this->postJson('/api/issue8664_categories', [
            'name' => 'tools',
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $category->assertStatus(201);

        $created = $this->postJson('/api/issue8664_items', [
            'code' => 'a',
            'category' => $category->json('@id'),
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $created->assertStatus(201);

        $item = Issue8664Item::query()->findOrFail($created->json('id'));
        $categoryId = $item->category_id;
        $this->assertNotNull($categoryId);

        $response = $this->patchJson($created->json('@id'), [
            'code' => 'b',
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/merge-patch+json']);

        $response->assertStatus(200);
        $item->refresh();
        $this->assertSame($categoryId, $item->category_id);
        $this->assertSame('b', $item->code);
    }
}
