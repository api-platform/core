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

class DocsSingleUiTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('api-platform.swagger_ui.enabled', true);
            $config->set('api-platform.redoc.enabled', false);
            $config->set('api-platform.scalar.enabled', false);
        });
    }

    public function testHtmlDocsHasNoOtherUiLinksWhenOnlyOneUiEnabled(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = $res->getContent();

        $this->assertStringContainsString('init-swagger-ui.js', $content);
        $this->assertStringContainsString('id="formats"', $content);
        $this->assertStringNotContainsString('>Swagger UI</a>', $content);
        $this->assertStringNotContainsString('>ReDoc</a>', $content);
        $this->assertStringNotContainsString('>Scalar</a>', $content);
    }
}
