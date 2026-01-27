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
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Exceptions\CustomHandler;
use Workbench\App\Exceptions\CustomHandlerException;
use Workbench\App\Exceptions\CustomTestException;
use Workbench\Database\Factories\BookFactory;

/**
 * Tests for issues #7058 and #7466.
 *
 * Ensures both custom exception handler classes and callbacks registered via renderable()
 * work correctly for non-API routes while API Platform operations use their own error handling.
 */
class CustomExceptionHandlerTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    protected static bool $customHandlerCalled = false;
    protected static bool $useCustomHandlerClass = false;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('app.debug', false);
            $config->set('api-platform.resources', [app_path('Models'), app_path('ApiResource')]);
        });
    }

    protected function resolveApplicationExceptionHandler($app): void
    {
        $handlerClass = self::$useCustomHandlerClass ? CustomHandler::class : \Illuminate\Foundation\Exceptions\Handler::class;
        $app->singleton(ExceptionHandler::class, $handlerClass);
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$customHandlerCalled = false;

        if (!self::$useCustomHandlerClass) {
            $this->app->make(ExceptionHandler::class)->renderable(static function (\Throwable $exception, Request $request) {
                if ($exception instanceof CustomTestException) {
                    self::$customHandlerCalled = true;

                    return new Response('Custom handler response', 418);
                }
            });
        }

        Route::get('/non-api-route', static function () {
            throw new CustomTestException('This should be handled by custom handler');
        });

        Route::get('/non-api-route-regular', static function () {
            throw new \RuntimeException('Regular exception on non-API route');
        });

        Route::get('/non-api-custom-handler', static function () {
            throw new CustomHandlerException('Should use custom handler class');
        });
    }

    public function testCustomExceptionHandlerIsCalledForNonApiRoutes(): void
    {
        $response = $this->get('/non-api-route');

        $this->assertTrue(self::$customHandlerCalled, 'Custom exception handler should be called for non-API routes');
        $response->assertStatus(418);
        $this->assertEquals('Custom handler response', $response->getContent());
    }

    public function testCustomExceptionHandlerIsNotCalledForApiRoutes(): void
    {
        BookFactory::new()->count(1)->create();

        $response = $this->get('/api/books/non-existent-id', ['accept' => 'application/ld+json']);

        $this->assertFalse(self::$customHandlerCalled, 'Custom exception handler should NOT be called for API Platform operations');
        $response->assertStatus(404);
    }

    public function testRegularExceptionOnNonApiRoute(): void
    {
        $response = $this->get('/non-api-route-regular');

        $response->assertStatus(500);
    }

    public function testApiPlatformExceptionHandlingStillWorks(): void
    {
        $response = $this->get('/api/books/invalid-id', ['accept' => 'application/ld+json']);

        $response->assertStatus(404);
        $this->assertStringContainsString('application/', $response->headers->get('content-type'));
    }

    public function testCustomHandlerClassWorksForNonApiRoutes(): void
    {
        self::$useCustomHandlerClass = true;
        $this->refreshApplication();
        $this->setUpTraits();
        CustomHandler::$customRenderCalled = false;

        Route::get('/non-api-custom-handler-test', static function () {
            throw new CustomHandlerException('Should use custom handler class');
        });

        $response = $this->get('/non-api-custom-handler-test');

        $this->assertTrue(CustomHandler::$customRenderCalled, 'Custom handler class render() should be called');
        $response->assertStatus(419);
        $this->assertEquals('Custom Handler Class Response', $response->getContent());

        self::$useCustomHandlerClass = false;
    }

    public function testCustomHandlerClassDoesNotInterceptApiRoutes(): void
    {
        self::$useCustomHandlerClass = true;
        $this->refreshApplication();
        $this->setUpTraits();
        CustomHandler::$customRenderCalled = false;

        BookFactory::new()->count(1)->create();

        $response = $this->get('/api/books/non-existent-id', ['accept' => 'application/ld+json']);

        $this->assertFalse(CustomHandler::$customRenderCalled, 'Custom handler class should not be called for API operations');
        $response->assertStatus(404);

        self::$useCustomHandlerClass = false;
    }
}
