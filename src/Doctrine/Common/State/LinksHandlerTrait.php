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

namespace ApiPlatform\Doctrine\Common\State;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Psr\Container\ContainerInterface;

trait LinksHandlerTrait
{
    private ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory;
    private ?ContainerInterface $handleLinksLocator;

    /**
     * @return Link[]
     */
    private function getLinks(string $resourceClass, Operation $operation, array $context): array
    {
        $links = $this->getOperationLinks($operation);

        if (!($linkClass = $context['linkClass'] ?? false)) {
            return $links;
        }

        $newLink = null;
        $linkProperty = $context['linkProperty'] ?? null;

        foreach ($links as $link) {
            if ($linkClass === $link->getFromClass() && $linkProperty === $link->getFromProperty()) {
                $newLink = $link;
                break;
            }
        }

        if ($newLink) {
            return [$newLink];
        }

        if (!$this->resourceMetadataCollectionFactory) {
            return [];
        }

        // Using GraphQL, it's possible that we won't find a GraphQL Operation of the same type (e.g. it is disabled).
        try {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($linkClass);
            $linkedOperation = $resourceMetadataCollection->getOperation($operation->getName());
        } catch (OperationNotFoundException $e) {
            if (!$operation instanceof GraphQlOperation) {
                throw $e;
            }

            // Instead, we'll look for the first Query available.
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                foreach ($resourceMetadata->getGraphQlOperations() as $op) {
                    if ($op instanceof Query) {
                        $linkedOperation = $op;
                    }
                }
            }
        }

        foreach ($this->getOperationLinks($linkedOperation ?? null) as $link) {
            if ($resourceClass === $link->getToClass() && $linkProperty === $link->getFromProperty()) {
                $newLink = $link;
                break;
            }
        }

        if (!$newLink) {
            throw new RuntimeException(sprintf('The class "%s" cannot be retrieved from "%s".', $resourceClass, $linkClass));
        }

        return [$newLink];
    }

    private function getIdentifierValue(array &$identifiers, ?string $name = null): mixed
    {
        if (isset($identifiers[$name])) {
            $value = $identifiers[$name];
            unset($identifiers[$name]);

            return $value;
        }

        return array_shift($identifiers);
    }

    private function getOperationLinks(?Operation $operation = null): array
    {
        if ($operation instanceof GraphQlOperation) {
            return $operation->getLinks() ?? [];
        }

        if ($operation instanceof HttpOperation) {
            return $operation->getUriVariables() ?? [];
        }

        return [];
    }

    private function getLinksHandler(Operation $operation): ?callable
    {
        if (!($options = $operation->getStateOptions()) || !method_exists($options, 'getHandleLinks') || null === $options->getHandleLinks()) {
            return null;
        }

        $handleLinks = $options->getHandleLinks(); // @phpstan-ignore-line method_exists called above
        if (\is_callable($handleLinks)) {
            return $handleLinks;
        }

        if ($this->handleLinksLocator && \is_string($handleLinks) && $this->handleLinksLocator->has($handleLinks)) {
            return [$this->handleLinksLocator->get($handleLinks), 'handleLinks'];
        }

        throw new RuntimeException(sprintf('Could not find handleLinks service "%s"', $handleLinks));
    }
}
