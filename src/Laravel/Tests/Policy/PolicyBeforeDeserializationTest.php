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

namespace ApiPlatform\Laravel\Tests\Policy;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\OwnedNote;
use Workbench\App\Policies\OwnedNotePolicy;
use Workbench\Database\Factories\OwnedNoteFactory;
use Workbench\Database\Factories\UserFactory;

class PolicyBeforeDeserializationTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('api-platform.graphql.enabled', true);
        Gate::guessPolicyNamesUsing(static fn (string $modelClass) => OwnedNote::class === $modelClass ? OwnedNotePolicy::class : null);
    }

    public function testPatchPolicyIsEvaluatedOnTheStoredRecord(): void
    {
        $owner = UserFactory::new()->create();
        $attacker = UserFactory::new()->create();
        $note = OwnedNoteFactory::new()->create(['user_id' => $owner->getKey(), 'title' => 'original']);

        $response = $this->actingAs($attacker)->patchJson(
            '/api/owned_notes/'.$note->getKey(),
            ['userId' => $attacker->getKey(), 'title' => 'hijacked'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response->assertStatus(403);
        $stored = OwnedNote::findOrFail($note->getKey());
        $this->assertSame($owner->getKey(), $stored->user_id);
        $this->assertSame('original', $stored->title);
    }

    public function testGraphQlCreateMutationUsesTheCreatePolicy(): void
    {
        $user = UserFactory::new()->create();

        $response = $this->actingAs($user)->postJson('/api/graphql', ['query' => \sprintf('mutation {
    createOwnedNote(input: {title: "x", userId: %d}) {
      ownedNote {id}
    }
  }', $user->getKey())], ['accept' => ['application/json']]);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('errors', $response->json(), (string) ($response->json()['errors'][0]['message'] ?? ''));
        $this->assertSame(1, OwnedNote::where('title', 'x')->count());
    }
}
