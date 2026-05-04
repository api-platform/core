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
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\ApiResource\Issue7945;
use Workbench\App\Policies\Issue7945Policy;

class Issue7945Test extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        Gate::guessPolicyNamesUsing(static fn (string $modelClass) => Issue7945::class === $modelClass ? Issue7945Policy::class : null);
    }

    public function testPolicyGrantsAccessWhenBodyIsNull(): void
    {
        $response = $this->postJson('/api/issue7945/import', [], ['accept' => 'application/ld+json']);
        $response->assertStatus(202);
    }
}
