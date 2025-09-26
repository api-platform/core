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
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class ValidationTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('api-platform.formats', ['jsonld' => ['application/ld+json']]);
            $config->set('api-platform.docs_formats', ['jsonld' => ['application/ld+json']]);
        });
    }

    public function testValidationCamelCase(): void
    {
        $data = [
            'surName' => '',
        ];

        $response = $this->postJson('/api/issue_6932', $data, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertJsonFragment(['violations' => [['propertyPath' => 'surName', 'message' => 'The sur name field is required.']]]); // validate that the name has been converted
        $response->assertStatus(422);
    }

    public function testCamelCaseValid(): void
    {
        $data = [
            'surName' => 'ok',
        ];

        $response = $this->postJson('/api/issue_6932', $data, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(201);
    }

    public function testValidationSnakeCase(): void
    {
        $data = [
            'sur_name' => 'test',
        ];

        $response = $this->postJson('/api/issue_6932', $data, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(422);
    }

    public function testRouteWithRequirements(): void
    {
        $response = $this->get('api/issue_7194_requirements/test', ['accept' => 'application/ld+json']);
        $response->assertStatus(404);
        $response = $this->get('api/issue_7194_requirements/1', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
    }

    public function testGetCollectionWithFormRequestValidation(): void
    {
        $response = $this->get('/api/slots/dropoff', ['accept' => 'application/ld+json']);
        $response->assertStatus(422);
        $response->assertJsonFragment(['violations' => [
            ['propertyPath' => 'pickupDate', 'message' => 'The pickup date field is required.'],
            ['propertyPath' => 'pickupSlotId', 'message' => 'The pickup slot id field is required.'],
        ]]);
    }
}
