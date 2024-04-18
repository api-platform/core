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

namespace ApiPlatform\GraphQl\State\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\State\ProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * This provider calls a GraphQl resolver if defined.
 */
final class ResolverProvider implements ProviderInterface
{
    use ClassInfoTrait;

    public function __construct(private readonly ProviderInterface $decorated, private readonly ContainerInterface $queryResolverLocator)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $item = $this->decorated->provide($operation, $uriVariables, $context);

        if (!$operation instanceof GraphQlOperation || null === ($queryResolverId = $operation->getResolver())) {
            return $item;
        }

        /** @var \ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface $queryResolver */
        $queryResolver = $this->queryResolverLocator->get($queryResolverId);
        $item = $queryResolver($item, $context);
        if (!$operation instanceof CollectionOperationInterface) {
            // The item retrieved can be of another type when using an identifier (see Relay Nodes at query.feature:23)
            $this->getResourceClass($item, $operation->getOutput()['class'] ?? $operation->getClass(), sprintf('Custom query resolver "%s"', $queryResolverId).' has to return an item of class %s but returned an item of class %s.');
        }

        return $item;
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function getResourceClass(?object $item, ?string $resourceClass, string $errorMessage = 'Resolver only handles items of class %s but retrieved item is of class %s.'): string
    {
        if (null === $item) {
            if (null === $resourceClass) {
                throw new \UnexpectedValueException('Resource class cannot be determined.');
            }

            return $resourceClass;
        }

        $itemClass = $this->getObjectClass($item);

        if (null === $resourceClass) {
            return $itemClass;
        }

        if ($resourceClass !== $itemClass && !$item instanceof $resourceClass) {
            throw new \UnexpectedValueException(sprintf($errorMessage, (new \ReflectionClass($resourceClass))->getShortName(), (new \ReflectionClass($itemClass))->getShortName()));
        }

        return $resourceClass;
    }
}
