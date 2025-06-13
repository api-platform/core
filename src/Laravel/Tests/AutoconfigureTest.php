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
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class AutoconfigureTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    public function testServiceProvider(): void
    {
        $response = $this->get('/api/custom_service_provider', headers: ['accept' => ['application/ld+json']]);
        $this->assertEquals($response->json()['test'], 'ok');
        $response->assertSuccessful();
    }

    public function testServiceProviderWithDependency(): void
    {
        $response = $this->get('/api/custom_service_provider_with_dependency', headers: ['accept' => ['application/ld+json']]);
        $this->assertEquals($response->json()['test'], 'test');
        $response->assertSuccessful();
    }
}
