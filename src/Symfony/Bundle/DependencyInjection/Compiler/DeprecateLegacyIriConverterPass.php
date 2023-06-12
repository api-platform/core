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

namespace ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal calls `setDeprecated` method with valid arguments depending on which version of symfony/dependency-injection is used
 */
final class DeprecateLegacyIriConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasAlias(IriConverterInterface::class)) {
            $container
                ->getAlias(IriConverterInterface::class)
                ->setDeprecated(...$this->buildDeprecationArgs('Using "%alias_id%" is deprecated since API Platform 2.7. Use "ApiPlatform\Api\IriConverterInterface" instead.'));
        }

        if ($container->hasDefinition(IriConverterInterface::class)) {
            $container
                ->getDefinition(IriConverterInterface::class)
                ->setDeprecated(...$this->buildDeprecationArgs('Using "%service_id%" is deprecated since API Platform 2.7. Use "ApiPlatform\Api\IriConverterInterface" instead.'));
        }

        if ($container->hasDefinition('api_platform.iri_converter.legacy')) {
            $container
                ->getDefinition('api_platform.iri_converter.legacy')
                ->setDeprecated(...$this->buildDeprecationArgs('Using "%service_id%" is deprecated since API Platform 2.7. Use "ApiPlatform\Api\IriConverterInterface" instead.'));
        }
    }

    private function buildDeprecationArgs(string $message): array
    {
        return method_exists(BaseNode::class, 'getDeprecation')
            ? ['api-platform/core', '2.7', $message]
            : [$message];
    }
}
