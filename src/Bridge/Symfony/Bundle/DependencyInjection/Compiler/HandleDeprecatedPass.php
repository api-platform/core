<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class HandleDeprecatedPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container
            ->setAlias('api_platform.doctrine.listener.mercure.publish', 'api_platform.doctrine.orm.listener.mercure.publish')
            ->setDeprecated(...$this->buildDeprecationArgs('2.6', 'Using "%alias_id%" service is deprecated since API Platform 2.6. Use "api_platform.doctrine.orm.listener.mercure.publish" instead.'));
    }

    private function buildDeprecationArgs(string $version, string $message): array
    {
        return method_exists(BaseNode::class, 'getDeprecation')
            ? ['api-platform/core', $version, $message]
            : [$message];
    }
}
