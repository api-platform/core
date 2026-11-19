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

class HeaderParameterValidationTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    public function testMissingRequiredHeaderIsRejected(): void
    {
        $response = $this->get('/api/with_required_header_parameter/1', ['Accept' => 'application/ld+json']);
        $response->assertStatus(422);
    }

    public function testPresentRequiredHeaderIsAccepted(): void
    {
        $response = $this->get('/api/with_required_header_parameter/1', ['Accept' => 'application/ld+json', 'X-Foo' => 'bar']);
        $response->assertStatus(200);
    }

    public function testQueryParameterNamedLikeHeaderDoesNotSatisfyHeaderConstraint(): void
    {
        $response = $this->get('/api/with_required_header_parameter/1?X-Foo=bar', ['Accept' => 'application/ld+json']);
        $response->assertStatus(422);
    }
}
