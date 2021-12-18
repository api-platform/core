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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ResourceInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ResourceInterfaceImplementation;

final class ResourceInterfaceImplementationProvider implements ProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        /** @var Operation */
        $operation = $context['operation'];
        if ($operation->isCollection()) {
            yield (new ResourceInterfaceImplementation())->setFoo('item1');
            yield (new ResourceInterfaceImplementation())->setFoo('item2');

            return;
        }

        if ('some-id' === $identifiers['id']) {
            return (new ResourceInterfaceImplementation())->setFoo('single item');
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        return ResourceInterface::class === $resourceClass;
    }
}
