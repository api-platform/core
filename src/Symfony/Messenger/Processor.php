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

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class Processor implements ProcessorInterface
{
    use ClassInfoTrait;
    use DispatchTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    private function persist(object $data, array $context = []): ?object
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

    private function remove(mixed $data): void
    {
        $this->dispatch(
            (new Envelope($data))
                ->with(new RemoveStamp())
        );
    }

    public function process(object $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if ($operation instanceof DeleteOperationInterface) {
            $this->remove($data);

            return $data;
        }

        return $this->persist($data, $context);
    }
}
