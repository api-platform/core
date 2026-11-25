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

use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

trait LinksHandlerTrait
{
    private ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory;
    private ?PropertyAccessorInterface $linksPropertyAccessor = null;

    /**
     * @param array{linkClass?: string, linkProperty?: string}&array<string, mixed> $context
     *
     * @return Link[]
     */
    private function getLinks(string $resourceClass, Operation $operation, array $context): array
    {
        $links = $this->getOperationLinks($operation);

        if (!($linkClass = $context['linkClass'] ?? false)) {
            // Root item lookup: GraphQl Query.links carries relation links (for nested traversal)
            // and the identifier-self link. Keep only the identifier-self link so handleLinks
            // applies WHERE id=X. A relation link always carries a fromProperty/toProperty (even a
            // self-reference whose toClass equals the resource class, e.g. a ManyToOne to self), so
            // excluding those prevents consuming the identifier via a bogus relation join.
            if ($operation instanceof GraphQlOperation) {
                return array_values(array_filter($links, static fn ($l) => !$l->getFromProperty() && !$l->getToProperty() && (null === $l->getToClass() || $l->getToClass() === $resourceClass)));
            }

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
            throw new RuntimeException(\sprintf('The class "%s" cannot be retrieved from "%s".', $resourceClass, $linkClass));
        }

        return [$newLink];
    }

    /**
     * @param array<int|string,mixed> $identifiers
     *
     * @return array<int|string,mixed>
     */
    private function resolveLinkIdentifiers(array $identifiers, Link $link, string $fromClass): array
    {
        $parameterName = $link->getParameterName();
        if (null === $parameterName) {
            return $identifiers;
        }

        $resolved = $identifiers[$parameterName] ?? null;
        $linkClass = $link->getFromClass();
        if (!($resolved instanceof $fromClass || (null !== $linkClass && $resolved instanceof $linkClass))) {
            return $identifiers;
        }

        $propertyAccessor = $this->linksPropertyAccessor ??= PropertyAccess::createPropertyAccessor();
        $values = [];
        foreach ($link->getIdentifiers() as $identifierProperty) {
            $values[$identifierProperty] = $propertyAccessor->getValue($resolved, $identifierProperty);
        }

        if (1 === \count($values)) {
            $identifiers[$parameterName] = current($values);

            return $identifiers;
        }

        $result = [];
        foreach ($identifiers as $key => $value) {
            if ($key === $parameterName) {
                $result += $values;
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array<int|string,mixed> $identifiers
     */
    private function getIdentifierValue(array &$identifiers, ?string $name = null): mixed
    {
        if (null !== $name && isset($identifiers[$name])) {
            $value = $identifiers[$name];
            unset($identifiers[$name]);

            return $value;
        }

        return array_shift($identifiers);
    }

    /**
     * @return Link[]|array
     */
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
}
