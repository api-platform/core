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

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;
use ApiPlatform\Metadata\Operation;

class LegacyDataPersisterProcessor implements ProcessorInterface
{
    public function __construct(private DataPersisterInterface $dataPersister)
    {
    }

    public function resumable(array $context = []): bool
    {
        if ($this->dataPersister instanceof ResumableDataPersisterInterface) {
            return $this->dataPersister->resumable($context);
        }

        return false;
    }

    public function supports($data, array $identifiers = [], array $context = []): bool
    {
        if ($supports = $this->dataPersister instanceof ContextAwareDataPersisterInterface ? $this->dataPersister->supports($data, $context) : $this->dataPersister->supports($data)) {
            return $supports;
        }

        return false;
    }

    public function process($data, array $identifiers = [], array $context = [])
    {
        if (Operation::METHOD_DELETE === $context['method']) {
            return $this->dataPersister instanceof ContextAwareDataPersisterInterface ? $this->dataPersister->remove($data, $context) : $this->dataPersister->remove($data);
        }

        return $this->dataPersister instanceof ContextAwareDataPersisterInterface ? $this->dataPersister->persist($data, $context) : $this->dataPersister->persist($data);
    }
}
