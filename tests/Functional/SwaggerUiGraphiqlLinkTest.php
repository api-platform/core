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
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwaggerUiGraphiqlLinkAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/graphiql_link';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/graphiql_link';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'enable_swagger_ui' => true,
                'graphql' => ['enabled' => false],
            ]);

            // the fixtures decorate a GraphQL service that is not registered once GraphQL is off
            if ($container->hasDefinition('app.graphql.type_converter')) {
                $container->removeDefinition('app.graphql.type_converter');
            }
        });
    }
}

final class SwaggerUiGraphiqlLinkTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return SwaggerUiGraphiqlLinkAppKernel::class;
    }

    public function testGraphiqlLinkIsHiddenWhenGraphQlIsDisabled(): void
    {
        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('graphiql-link', $client->getResponse()->getContent());
    }
}
