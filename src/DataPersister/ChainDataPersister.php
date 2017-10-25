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

namespace ApiPlatform\Core\DataPersister;

/**
 * Chained data persisters.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ChainDataPersister implements DataPersisterInterface
{
    private $persisters;

    /**
     * @param DataPersisterInterface[] $persisters
     */
    public function __construct(array $persisters)
    {
        $this->persisters = $persisters;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        foreach ($this->persisters as $persister) {
            if ($persister->supports($data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        foreach ($this->persisters as $persister) {
            if ($persister->supports($data)) {
                $persister->persist($data);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data)
    {
        foreach ($this->persisters as $persister) {
            if ($persister->supports($data)) {
                $persister->remove($data);
            }
        }
    }
}
