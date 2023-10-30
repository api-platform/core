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
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ResourceInterfaceImplementation;

final class ResourceInterfaceImplementationProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof CollectionOperationInterface) {
            return (function () {
                yield (new ResourceInterfaceImplementation())->setFoo('item1');
                yield (new ResourceInterfaceImplementation())->setFoo('item2');
            })();
        }

        return 'some-id' === $uriVariables['foo'] ? (new ResourceInterfaceImplementation())->setFoo('single item') : null;
    }
}
