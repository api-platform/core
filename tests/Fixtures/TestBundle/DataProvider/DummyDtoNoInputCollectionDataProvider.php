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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto as OutputDtoDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;
use Doctrine\Persistence\ManagerRegistry;

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

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return \in_array($resourceClass, [DummyDtoNoInput::class, DummyDtoNoInputDocument::class], true);
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        /** @var DummyDtoNoInput[]|DummyDtoNoInputDocument[] $dummyDtos */
        $dummyDtos = $this->registry->getManagerForClass($resourceClass)->getRepository($resourceClass)->findAll();
        $objects = [];
        foreach ($dummyDtos as $dummyDto) {
            $object = DummyDtoNoInput::class === $resourceClass ? new OutputDto() : new OutputDtoDocument();
            $object->bat = $dummyDto->lorem;
            $object->baz = $dummyDto->ipsum;
            $objects[] = $object;
        }

        return new ArrayPaginator($objects, 0, \count($objects));
    }
}
