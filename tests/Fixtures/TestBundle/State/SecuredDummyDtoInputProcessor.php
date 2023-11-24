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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\SecuredDummyInputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SecuredDummyWithInputDto;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

final class SecuredDummyDtoInputProcessor implements ProcessorInterface
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param SecuredDummyInputDto|mixed $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof SecuredDummyInputDto) {
            throw new \RuntimeException('Data is not an SecuredDummyInputDto');
        }

        /** @var EntityManager */
        $manager = $this->registry->getManagerForClass($operation->getClass());
        /** @var SecuredDummyWithInputDto $entity */
        $entity = new ($operation->getClass())();

        if (isset($context['previous_data'])) {
            /** @var SecuredDummyWithInputDto $entity */
            $entity = $manager->getReference($operation->getClass(), $context['previous_data']->getId());
        }

        $entity->setTitle($data->title);
        $entity->setDescription($data->description);

        if (isset($data->adminOnlyProperty)) {
            $entity->setAdminOnlyProperty($data->adminOnlyProperty);
        }

        $manager->persist($entity);
        $manager->flush();

        return $entity;
    }
}
