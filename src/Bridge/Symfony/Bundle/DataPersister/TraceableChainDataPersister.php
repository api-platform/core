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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DataPersister;

use ApiPlatform\Core\DataPersister\ChainDataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class TraceableChainDataPersister implements DataPersisterInterface
{
    private $persisters = [];
    private $persistersResponse = [];
    private $decorated;

    public function __construct(DataPersisterInterface $dataPersister)
    {
        if ($dataPersister instanceof ChainDataPersister) {
            $this->decorated = $dataPersister;
            $this->persisters = $dataPersister->persisters;
        }
    }

    public function getPersistersResponse(): array
    {
        return $this->persistersResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $this->decorated->supports($data);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        if ($match = $this->tracePersisters($data)) {
            return $match->persist($data) ?? $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data)
    {
        if ($match = $this->tracePersisters($data)) {
            return $match->remove($data);
        }
    }

    private function tracePersisters($data)
    {
        $match = null;
        foreach ($this->persisters as $persister) {
            $this->persistersResponse[\get_class($persister)] = $match ? null : false;
            if (!$match && $persister->supports($data)) {
                $match = $persister;
                $this->persistersResponse[\get_class($persister)] = true;
            }
        }

        return $match;
    }
}
