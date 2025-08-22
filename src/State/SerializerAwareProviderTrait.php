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

namespace ApiPlatform\State;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Injects serializer in providers.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
trait SerializerAwareProviderTrait
{
    /**
     * @internal
     */
    private ContainerInterface $serializerLocator;

    public function setSerializerLocator(ContainerInterface $serializerLocator): void
    {
        trigger_deprecation(
            'api-platform/core',
            '4.2',
            'The "%s" interface is deprecated and will be removed in 5.0. It violates the dependency injection principle.',
            SerializerAwareProviderInterface::class
        );

        $this->serializerLocator = $serializerLocator;
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->serializerLocator->get('serializer');
    }
}
