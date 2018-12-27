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

namespace ApiPlatform\Core\Bridge\Symfony\Messenger;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Dispatches the given resource using the message bus of Symfony Messenger.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DataPersister implements DataPersisterInterface
{
    use ClassInfoTrait;

    private $resourceMetadataFactory;
    private $messageBus;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, MessageBusInterface $messageBus)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->messageBus = $messageBus;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return true === $this->resourceMetadataFactory->create($this->getObjectClass($data))->getAttribute('messenger');
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        $envelope = $this->messageBus->dispatch($data);
        if (null === $stamp = $envelope->last(HandledStamp::class)) {
            return $data;
        }

        return $stamp->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data)
    {
        $this->messageBus->dispatch(new Envelope($data, new RemoveStamp()));
    }
}
