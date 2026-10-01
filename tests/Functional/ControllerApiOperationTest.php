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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ControllerApiOperationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CheckoutOutput::class];
    }

    public function testPostRunsPipeline(): void
    {
        self::createClient()->request('POST', '/controller_api_operation/checkout', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
            'json' => ['reference' => 'abc'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@type' => 'CheckoutOutput',
            'reference' => 'abc',
            'status' => 'confirmed',
        ]);
    }

    public function testValidationRuns(): void
    {
        self::createClient()->request('POST', '/controller_api_operation/checkout', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
            'json' => ['reference' => ''],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDocumentedInOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('post', $response->toArray()['paths']['/controller_api_operation/checkout']);
    }
}
