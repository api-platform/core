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

namespace ApiPlatform\Core\Bridge\Doctrine\Common;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;

/**
 * Data persister for Doctrine.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class DataPersister implements DataPersisterInterface
{
    use ClassInfoTrait;

    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return null !== $this->getManager($data);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data)
    {
        if (!$manager = $this->getManager($data)) {
            return;
        }

        $manager->persist($data);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data)
    {
        if (!$manager = $this->getManager($data)) {
            return;
        }

        $manager->remove($data);
        $manager->flush();
    }

    /**
     * Gets the Doctrine object manager associated with given data.
     *
     * @param mixed $data
     *
     * @return DoctrineObjectManager|null
     */
    private function getManager($data)
    {
        return is_object($data) ? $this->managerRegistry->getManagerForClass($this->getObjectClass($data)) : null;
    }
}
