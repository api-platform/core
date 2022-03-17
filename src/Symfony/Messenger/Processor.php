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

namespace ApiPlatform\Symfony\Messenger;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class Processor implements ProcessorInterface
{
    use ClassInfoTrait;
    use DispatchTrait;

    /**
     * @var ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface
     */
    private $resourceMetadataCollectionFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, MessageBusInterface $messageBus)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->messageBus = $messageBus;
    }

    /**
     * {@inheritdoc}
     */
    private function persist($data, array $context = [])
    {
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
    private function remove($data, array $context = [])
    {
        $this->dispatch(
            (new Envelope($data))
                ->with(new RemoveStamp())
        );
    }

    public function process($data, array $uriVariables = [], ?string $operationName = null, array $context = [])
    {
        if (\array_key_exists('operation', $context) && $context['operation']->isDelete()) {
            return $this->remove($data);
        }

        return $this->persist($data);
    }

    public function supports($data, array $uriVariables = [], ?string $operationName = null, array $context = []): bool
    {
        try {
            if (isset($context['operation'])) {
                $operation = $context['operation'];
            } else {
                $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($context['resource_class'] ?? $this->getObjectClass($data));
                $operation = $resourceMetadataCollection->getOperation($operationName);
            }

            return false !== ($operation->getMessenger() ?? false);
        } catch (OperationNotFoundException $e) {
            return false;
        }
    }
}
