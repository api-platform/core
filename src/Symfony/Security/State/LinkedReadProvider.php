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

namespace ApiPlatform\Symfony\Security\State;

use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Checks if the linked resources have security attributes and prepares them for access checking.
 *
 * @experimental
 */
final class LinkedReadProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $decorated,
        private readonly ProviderInterface $locator,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->decorated->provide($operation, $uriVariables, $context);

        if (!$operation instanceof HttpOperation) {
            return $data;
        }

        $request = ($context['request'] ?? null);

        if ($operation->getUriVariables()) {
            foreach ($operation->getUriVariables() as $key => $uriVariable) {
                if (!$uriVariable instanceof Link || !$uriVariable->getSecurity()) {
                    continue;
                }

                $relationClass = $uriVariable->getFromClass() ?? $uriVariable->getToClass();

                if (!$relationClass) {
                    continue;
                }

                $parentOperation = $this->resourceMetadataCollectionFactory
                    ->create($relationClass)
                    ->getOperation($operation->getExtraProperties()['parent_uri_template'] ?? null);
                try {
                    $relation = $this->locator->provide($parentOperation, [$uriVariable->getIdentifiers()[0] => $request?->attributes->all()[$key]], $context);
                } catch (ProviderNotFoundException) {
                    $relation = null;
                }

                if (!$relation) {
                    throw new NotFoundHttpException('Relation for link security not found.');
                }

                try {
                    $securityObjectName = $uriVariable->getSecurityObjectName();

                    if (!$securityObjectName) {
                        $securityObjectName = $uriVariable->getToProperty() ?? $uriVariable->getFromProperty();
                    }

                    $request?->attributes->set($securityObjectName, $relation);
                } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
                    throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
                }
            }
        }

        return $data;
    }
}
