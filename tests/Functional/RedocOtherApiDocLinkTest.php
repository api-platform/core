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

class RedocOtherApiDocLinkAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/redoc_otherapidoc_link';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/redoc_otherapidoc_link';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'enable_swagger_ui' => false,
                'enable_re_doc' => true,
                'enable_scalar' => false,
                'graphql' => ['enabled' => false],
            ]);

            // the fixtures decorate a GraphQL service that is not registered once GraphQL is off
            if ($container->hasDefinition('app.graphql.type_converter')) {
                $container->removeDefinition('app.graphql.type_converter');
            }
        });
    }
}

final class RedocOtherApiDocLinkTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return RedocOtherApiDocLinkAppKernel::class;
    }

    public function testOtherApiDocsLinkAreHiddenWhenAllOptionsAreDisabled(): void
    {
        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Other API docs:', $client->getResponse()->getContent());
    }
}
