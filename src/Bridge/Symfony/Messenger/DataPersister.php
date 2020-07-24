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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
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
final class DataPersister implements ContextAwareDataPersisterInterface
{
    use ClassInfoTrait;
    use DispatchTrait;

    private $resourceMetadataFactory;
    private $dataPersister;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, MessageBusInterface $messageBus, ContextAwareDataPersisterInterface $dataPersister)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->messageBus = $messageBus;
        $this->dataPersister = $dataPersister;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        if (true === ($context['messenger_dispatched'] ?? false)) {
            return false;
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($context['resource_class'] ?? $this->getObjectClass($data));
        } catch (ResourceClassNotFoundException $e) {
            return false;
        }

        return false !== $this->getMessengerAttributeValue($resourceMetadata, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = [])
    {
        if ($this->handOver($data, $context)) {
            $data = $this->dataPersister->persist($data, $context + ['messenger_dispatched' => true]);
        }

        $envelope = $this->dispatch(
            (new Envelope($data))
                ->with(new ContextStamp($context))
        );

        $handledStamp = $envelope->last(HandledStamp::class);
        if (!$handledStamp instanceof HandledStamp) {
            return $data;
        }

        return $handledStamp->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = [])
    {
        if ($this->handOver($data, $context)) {
            $this->dataPersister->remove($data, $context + ['messenger_dispatched' => true]);
        }

        $this->dispatch(
            (new Envelope($data))
                ->with(new RemoveStamp())
        );
    }

    /**
     * Should this DataPersister hand over in "persist" mode?
     */
    private function handOver($data, array $context = []): bool
    {
        try {
            $value = $this->getMessengerAttributeValue($this->resourceMetadataFactory->create($context['resource_class'] ?? $this->getObjectClass($data)), $context);
        } catch (ResourceClassNotFoundException $exception) {
            return false;
        }

        return 'persist' === $value || (\is_array($value) && (\in_array('persist', $value, true) || (true === $value['persist'] ?? false)));
    }

    /**
     * @return bool|string|array|null
     */
    private function getMessengerAttributeValue(ResourceMetadata $resourceMetadata, array $context = [])
    {
        if (null !== $operationName = $context['collection_operation_name'] ?? $context['item_operation_name'] ?? null) {
            return $resourceMetadata->getTypedOperationAttribute(
                    $context['collection_operation_name'] ?? false ? OperationType::COLLECTION : OperationType::ITEM,
                    $operationName,
                    'messenger',
                    false,
                    true
                );
        }

        if (isset($context['graphql_operation_name'])) {
            return $resourceMetadata->getGraphqlAttribute($context['graphql_operation_name'], 'messenger', false, true);
        }

        return $resourceMetadata->getAttribute('messenger', false);
    }
}
