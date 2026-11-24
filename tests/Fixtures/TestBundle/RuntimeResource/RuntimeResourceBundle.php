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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RuntimeResourceBundle extends Bundle
{
    private static ?\Closure $resources = null;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RuntimeValidatorLoaderPass());
    }

    public function boot(): void
    {
        $container = $this->container;
        if (null === self::$resources) {
            spl_autoload_register([self::class, 'load']);
        }

        self::$resources = static fn (): RuntimeResources => $container->get(RuntimeResources::class);
    }

    public function shutdown(): void
    {
        spl_autoload_unregister([self::class, 'load']);
        self::$resources = null;
    }

    public static function load(string $class): void
    {
        if (null !== self::$resources && null !== (self::$resources)()->get($class)) {
            eval(\sprintf('namespace %s; final class %s extends \\%s {}', RuntimeResources::NAMESPACE, substr($class, \strlen(RuntimeResources::NAMESPACE) + 1), RuntimeObject::class));
        }
    }
}
