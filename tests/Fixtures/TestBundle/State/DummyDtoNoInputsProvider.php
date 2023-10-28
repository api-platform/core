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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto as OutputDtoDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;

final class DummyDtoNoInputsProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $itemProvider, private readonly ProviderInterface $collectionProvider)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|OutputDto|OutputDtoDocument
    {
        if ($operation instanceof CollectionOperationInterface) {
            $object = $this->collectionProvider->provide($operation, $uriVariables, $context);
            foreach ($object as &$v) {
                $v = $this->toOutput($v);
            }

            return $object;
        }

        $object = $this->itemProvider->provide($operation, $uriVariables, $context);

        return $this->toOutput($object);
    }

    private function toOutput(DummyDtoNoInput|DummyDtoNoInputDocument $object): OutputDto|OutputDtoDocument
    {
        $output = $object instanceof DummyDtoNoInput ? new OutputDto() : new OutputDtoDocument();
        $output->id = $object->getId();
        $output->bat = (string) $object->lorem;
        $output->baz = (float) $object->ipsum;

        return $output;
    }
}
