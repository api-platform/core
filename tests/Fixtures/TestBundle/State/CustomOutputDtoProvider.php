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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\CustomOutputDto;

final class CustomOutputDtoProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $itemProvider, private readonly ProviderInterface $collectionProvider)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $object = $this->collectionProvider->provide($operation, $uriVariables, $context);

            foreach ($object as &$value) {
                $value = $this->toOutput($value);
            }

            return $object;
        }

        return $this->toOutput($this->itemProvider->provide($operation, $uriVariables, $context));
    }

    private function toOutput($object): CustomOutputDto
    {
        $output = new CustomOutputDto();
        $output->foo = $object->lorem;
        $output->bar = (int) $object->ipsum;

        return $output;
    }
}
