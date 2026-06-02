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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

/**
 * @see https://github.com/api-platform/core/issues/7981
 */
class DenormalizationValidationTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('api-platform.formats', ['jsonld' => ['application/ld+json']]);
            $config->set('api-platform.docs_formats', ['jsonld' => ['application/ld+json']]);
        });
    }

    public function testWrongTypeOnTypedDtoWithRuleProduces422(): void
    {
        $response = $this->postJson(
            '/api/issue6745/rule_validations',
            ['prop' => 'abc'],
            ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']
        );

        $response->assertStatus(422);
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame('ValidationError', $body['@type'] ?? null);
        $this->assertNotEmpty($body['violations'] ?? []);
        $this->assertSame('prop', $body['violations'][0]['propertyPath']);
    }

    public function testWrongTypeWithoutRuleRethrows(): void
    {
        // `max` rule is `lt:2` (no required, no type rule) — but per the rule table, ANY rule
        // on the property triggers a generic Type @ 422 (consistent with Symfony's
        // "any wrong type | any other constraint" branch).
        $response = $this->postJson(
            '/api/issue6745/rule_validations',
            ['max' => 'abc'],
            ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']
        );

        $response->assertStatus(422);
    }

    public function testEloquentNullOnRequiredFieldStillReturns422(): void
    {
        // Eloquent dynamic attrs → no denormalization error.  Validation layer catches null + required.
        $response = $this->postJson(
            '/api/issue_6932',
            ['sur_name' => null],
            ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']
        );

        $response->assertStatus(422);
    }
}
