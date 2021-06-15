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

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;

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
        if ($this->dataPersister instanceof ResumableDataPersisterInterface) {
            return $this->dataPersister->supports($context);
        }

        return false;
    }

    public function process($data, array $identifiers = [], array $context = [])
    {
        dd('passons');

    }
}
