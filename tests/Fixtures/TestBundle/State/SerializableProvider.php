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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
readonly class SerializableProvider implements ProviderInterface, ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedServices(): array
    {
        return ['serializer' => SerializerInterface::class];
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        return $this->getSerializer()->deserialize(<<<'JSON'
{
    "id": 1,
    "foo": "Lorem",
    "bar": "Ipsum"
}
JSON, $operation->getClass(), 'json');
    }

    private function getSerializer(): SerializerInterface
    {
        if (!$this->container->has('serializer')) {
            throw new \LogicException('The serializer service is not available. Did you forget to install symfony/serializer?');
        }

        return $this->container->get('serializer');
    }
}
