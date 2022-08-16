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

namespace ApiPlatform\Doctrine\Common\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager as DoctrineObjectManager;

final class RemoveProcessor implements ProcessorInterface
{
    use ClassInfoTrait;

    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(object $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if (!$manager = $this->getManager($data)) {
            return null;
        }

        $manager->remove($data);
        $manager->flush();

        return null;
    }

    /**
     * Gets the Doctrine object manager associated with given data.
     */
    private function getManager($data): ?DoctrineObjectManager
    {
        return \is_object($data) ? $this->managerRegistry->getManagerForClass($this->getObjectClass($data)) : null;
    }
}
