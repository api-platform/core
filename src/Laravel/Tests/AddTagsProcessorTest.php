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
use Workbench\App\Purger\MockPurger;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;

class AddTagsProcessorTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('api-platform.http_cache.public', true);
        $app['config']->set('api-platform.http_cache.max_age', 60);
        $app['config']->set('api-platform.http_cache.invalidation.purger', MockPurger::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        MockPurger::reset();
    }

    public function testCollectionResponseCarriesCacheTagsHeader(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();

        $response = $this->getJson('/api/books', ['Accept' => 'application/ld+json']);

        $response->assertHeader('Cache-Tags');
        $this->assertStringContainsString('/api/books', $response->headers->get('Cache-Tags'));
    }
}
