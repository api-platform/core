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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\HeadSpyResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HeadRequestWithoutOptimizationAppKernel extends \AppKernel
{
    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/head_no_opt';
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/head_no_opt';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'enable_head_request_optimization' => false,
            ]);
        });
    }
}

/**
 * Opt-out: with enable_head_request_optimization disabled, a HEAD request must
 * behave like GET again — the body is built, so the (lazy) collection IS iterated.
 * The spy paginator throws a fixed 418 on iteration; seeing it proves the flag
 * restores the previous GET-equivalent behavior.
 */
final class HeadRequestWithoutOptimizationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [HeadSpyResource::class];
    }

    protected static function getKernelClass(): string
    {
        return HeadRequestWithoutOptimizationAppKernel::class;
    }

    public function testHeadIteratesCollectionWhenOptimizationDisabled(): void
    {
        self::createClient()->request('HEAD', '/head_spy_resources', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(418);
    }
}
