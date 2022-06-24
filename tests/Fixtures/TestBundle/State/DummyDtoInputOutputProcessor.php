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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoInputOutput;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

final class DummyDtoInputOutputProcessor implements ProcessorInterface
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param InputDto $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // TODO: mongodb
        $entity = new DummyDtoInputOutput();
        /** @var EntityManager */
        $manager = $this->registry->getManagerForClass(DummyDtoInputOutput::class);

        if (isset($context['previous_data'])) {
            $entity = $manager->getReference(DummyDtoInputOutput::class, $context['previous_data']->id);
        }

        $entity->str = $data->foo;
        $entity->num = $data->bar;
        $entity->relatedDummies = new ArrayCollection($data->relatedDummies);

        $manager->persist($entity);
        $manager->flush();

        $outputDto = new OutputDto();
        $outputDto->id = $entity->id;
        $outputDto->baz = $entity->num;
        $outputDto->bat = $entity->str;
        $outputDto->relatedDummies = (array) $data->relatedDummies;

        return $outputDto;
    }
}
