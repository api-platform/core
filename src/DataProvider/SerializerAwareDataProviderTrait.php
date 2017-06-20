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

namespace ApiPlatform\Core\DataProvider;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Inject serializer in data providers.
 *
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
trait SerializerAwareDataProviderTrait
{
    /**
     * @var ContainerInterface
     */
    private $serializerLocator;

    /**
     * @param ContainerInterface $serializerLocator
     */
    public function setSerializerLocator(ContainerInterface $serializerLocator)
    {
        $this->serializerLocator = $serializerLocator;
    }

    /**
     * @return SerializerInterface
     */
    private function getSerializer(): SerializerInterface
    {
        return $this->serializerLocator->get('serializer');
    }
}
