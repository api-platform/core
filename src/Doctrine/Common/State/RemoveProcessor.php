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
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager as DoctrineObjectManager;

final class RemoveProcessor implements ProcessorInterface
{
    use ClassInfoTrait;

    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$manager = $this->getManager($data)) {
            return;
        }

        $manager->remove($data);
        $manager->flush();
    }

    /**
     * Gets the Doctrine object manager associated with given data.
     */
    private function getManager($data): ?DoctrineObjectManager
    {
        return \is_object($data) ? $this->managerRegistry->getManagerForClass($this->getObjectClass($data)) : null;
    }
}
