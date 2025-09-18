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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7287\OperationWithDefaultFormat;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ErrorFormatAppKernel extends \AppKernel
{
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $def = $container->getDefinition('api_platform.error_listener');
                $def->setArgument(5, ['jsonld' => ['application/ld+json']]);
            }
        });
    }
}

final class ErrorFormatTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static function getKernelClass(): string
    {
        return ErrorFormatAppKernel::class;
    }

    protected static ?bool $alwaysBootKernel = true;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [OperationWithDefaultFormat::class];
    }

    public function testNotAcceptableXml(): void
    {
        self::createClient()->request('GET', '/operation_with_default_formats', [
            'headers' => ['accept' => 'text/xml'],
        ]);

        $this->assertJsonContains(['detail' => 'Requested format "text/xml" is not supported. Supported MIME types are "application/ld+json".']);
        $this->assertResponseStatusCodeSame(406);
    }
}
