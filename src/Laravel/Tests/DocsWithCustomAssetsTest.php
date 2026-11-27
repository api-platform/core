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
use Illuminate\Config\Repository;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DocsWithCustomAssetsTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            // The application overrides the views of the package in "<view path>/vendor/api-platform"
            $config->set('view.paths', [__DIR__.'/Fixtures/views', ...$config->get('view.paths', [])]);
        });
    }

    public function testApplicationViewsAddStylesheetsAndScripts(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = (string) $res->getContent();

        $this->assertStringContainsString('<link rel="stylesheet" href="/custom-docs.css">', $content);
        $this->assertStringContainsString('<script src="/custom-docs-plugin.js" data-ui="swagger"></script>', $content);
        // Loaded after the Swagger UI scripts, executed before the "load" event that initializes Swagger UI
        $this->assertGreaterThan(strpos($content, 'init-swagger-ui.js'), strpos($content, 'custom-docs-plugin.js'));
    }
}
