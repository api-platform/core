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

/**
 * Injects serializer in providers.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @deprecated in 4.2, to be removed in 5.0 because it violates the dependency injection principle.
 */
interface SerializerAwareProviderInterface
{
    /**
     * @return void
     */
    public function setSerializerLocator(ContainerInterface $serializerLocator);
}
