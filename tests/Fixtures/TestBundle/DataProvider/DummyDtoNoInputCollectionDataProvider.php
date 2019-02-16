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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class DummyDtoNoInputCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return \in_array($resourceClass, [DummyDtoNoInput::class, DummyDtoNoInputDocument::class], true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $dummyDtos = $this->registry->getManagerForClass($resourceClass)->getRepository($resourceClass)->findAll();
        $objects = [];
        foreach ($dummyDtos as $dummyDto) {
            $object = new OutputDto();
            $object->bat = $dummyDto->lorem;
            $object->baz = $dummyDto->ipsum;
            $objects[] = $object;
        }

        return $objects;
    }
}
