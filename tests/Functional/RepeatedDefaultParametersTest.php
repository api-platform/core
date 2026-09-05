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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

final class RepeatedDefaultParametersTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    protected static function getKernelClass(): string
    {
        return \RepeatedDefaultParametersAppKernel::class;
    }

    public function testRepeatedDefaultParametersAppearTogetherInOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $content = $response->toArray();

        foreach ($content['paths'] as $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (!isset($pathItem[$method]['parameters'])) {
                    continue;
                }

                $parameters = [];
                foreach ($pathItem[$method]['parameters'] as $parameter) {
                    if ('header' === $parameter['in']) {
                        $parameters[$parameter['name']] = $parameter;
                    }
                }

                if (!isset($parameters['API-Token'], $parameters['Request-ID'])) {
                    continue;
                }

                $this->assertSame('API-Token', $parameters['API-Token']['name']);
                $this->assertSame('header', $parameters['API-Token']['in']);
                $this->assertSame('API token', $parameters['API-Token']['description']);
                $this->assertTrue($parameters['API-Token']['required']);
                $this->assertSame('Request-ID', $parameters['Request-ID']['name']);
                $this->assertSame('header', $parameters['Request-ID']['in']);
                $this->assertSame('Request correlation identifier', $parameters['Request-ID']['description']);
                $this->assertFalse($parameters['Request-ID']['required']);

                return;
            }
        }

        $this->fail('No OpenAPI operation contained both repeated default header parameters.');
    }
}
