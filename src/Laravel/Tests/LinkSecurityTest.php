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
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\CommentFactory;

class LinkSecurityTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    public function testLinkSecurityIsEvaluated(): void
    {
        $comment = CommentFactory::new()->createOne();
        $uri = \sprintf('/api/posts/%d/secured_comments/%d', $comment->getAttribute('post_id'), $comment->getKey());

        Gate::define('view-secured-comment', static fn (?object $user): bool => true);
        $this->get($uri, ['accept' => 'application/ld+json'])->assertStatus(200);

        Gate::define('view-secured-comment', static fn (?object $user): bool => false);
        $this->get($uri, ['accept' => 'application/ld+json'])->assertStatus(403);
    }
}
