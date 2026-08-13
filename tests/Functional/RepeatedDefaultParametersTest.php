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

use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RepeatedDefaultParametersTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    protected static function getKernelClass(): string
    {
        return RepeatedDefaultParametersAppKernel::class;
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

final class RepeatedDefaultParametersAppKernel extends AppKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            if ($container->hasDefinition('phpunit_resource_name_collection')) {
                $container->removeDefinition('phpunit_resource_name_collection');
            }

            $container->loadFromExtension('api_platform', [
                'defaults' => [
                    'parameters' => [
                        'api_token' => [
                            'class' => HeaderParameter::class,
                            'key' => 'API-Token',
                            'required' => true,
                            'description' => 'API token',
                        ],
                        'request_id' => [
                            'class' => HeaderParameter::class,
                            'key' => 'Request-ID',
                            'required' => false,
                            'description' => 'Request correlation identifier',
                        ],
                    ],
                ],
            ]);
        });
    }
}
