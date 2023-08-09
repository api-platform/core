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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoInputOutput as DummyDtoInputOutputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\InputDto as InputDtoDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto as OutputDtoDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

final class DummyDtoInputOutputProcessor implements ProcessorInterface
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param InputDto|InputDtoDocument|mixed $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!($data instanceof InputDto || $data instanceof InputDtoDocument)) {
            throw new \RuntimeException('Data is not an InputDto');
        }

        /** @var EntityManager */
        $manager = $this->registry->getManagerForClass($operation->getClass());
        $entity = new ($operation->getClass())();

        if (isset($context['previous_data'])) {
            $entity = $manager->getReference($operation->getClass(), $context['previous_data']->id);
        }

        $entity->str = $data->foo;
        $entity->num = $data->bar;
        if ($data->relatedDummies) {
            $entity->relatedDummies = new ArrayCollection($data->relatedDummies);
        }

        $manager->persist($entity);
        $manager->flush();

        $outputDto = DummyDtoInputOutputDocument::class === $operation->getClass() ? new OutputDtoDocument() : new OutputDto();
        $outputDto->id = $entity->id;
        $outputDto->baz = $entity->num;
        $outputDto->bat = $entity->str;
        $outputDto->relatedDummies = (array) $data->relatedDummies;

        return $outputDto;
    }
}
