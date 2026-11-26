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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Orm;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource\EntityRow;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource\ResourceDefinition;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeMapper;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeObject;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeResources;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<RuntimeObject, RuntimeObject|null>
 */
final class EavProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly RuntimeMapper $mapper,
        private readonly EavProvider $provider,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?RuntimeObject
    {
        $manager = $this->registry->getManagerForClass(EntityRow::class);
        \assert($manager instanceof EntityManagerInterface && $data instanceof RuntimeObject);

        $definition = $manager->getRepository(ResourceDefinition::class)->findOneBy(['name' => RuntimeResources::nameOf($data::class)]);
        $row = $operation instanceof Post
            ? new EntityRow($definition)
            : $manager->getRepository(EntityRow::class)->findOneBy(['id' => $uriVariables['id'] ?? null, 'definition' => $definition]) ?? throw new NotFoundHttpException();

        if ($operation instanceof DeleteOperationInterface) {
            $manager->remove($row);
            $manager->flush();

            return null;
        }

        $row->replaceValues($this->mapper->extract($data));
        $manager->persist($row);
        $manager->flush();

        return $this->provider->find(RuntimeResources::nameOf($data::class), (int) $row->getId());
    }
}
