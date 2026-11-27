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

class DocsWithExtraConfigurationTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('api-platform.swagger_ui.persist_authorization', true);
            $config->set('api-platform.swagger_ui.extra_configuration', [
                'docExpansion' => 'list',
                'apiPlatformLogin' => [
                    ['operationId' => 'api_login_post', 'securityScheme' => 'JWT', 'tokenPath' => 'token'],
                ],
            ]);
        });
    }

    public function testSwaggerDataContainsExtraConfigurationAndPersistAuthorization(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = (string) $res->getContent();
        $this->assertStringContainsString('"persistAuthorization":true', $content);
        $this->assertStringContainsString('"extraConfiguration":{"docExpansion":"list","apiPlatformLogin":[{"operationId":"api_login_post","securityScheme":"JWT","tokenPath":"token"}]}', $content);
    }
}
