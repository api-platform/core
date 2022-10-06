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

namespace ApiPlatform\State;

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * An ItemProvider for POST operations on generated subresources.
 *
 * @see ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubresourceEmployee
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @experimental
 */
final class CreateProvider implements ProviderInterface
{
    public function __construct(private ProviderInterface $decorated, private ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if (!$uriVariables || !$operation instanceof HttpOperation || null !== $operation->getController()) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $operationUriVariables = $operation->getUriVariables();
        $relationClass = current($operationUriVariables)->getFromClass();
        $key = key($operationUriVariables);
        $relationUriVariables = [];

        foreach ($operationUriVariables as $parameterName => $value) {
            if ($key === $parameterName) {
                $relationUriVariables['id'] = new Link(identifiers: $value->getIdentifiers(), fromClass: $value->getFromClass(), parameterName: $key);
                continue;
            }

            $relationUriVariables[$parameterName] = $value;
        }

        $relation = $this->decorated->provide(new Get(uriVariables: $relationUriVariables, class: $relationClass), $uriVariables);
        try {
            $resource = new ($operation->getClass());
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('An error occurred while trying to create an instance of the "%s" resource. Consider writing your own "%s" implementation and setting it as `provider` on your operation instead.', $operation->getClass(), ProviderInterface::class), 0, $e);
        }
        $this->propertyAccessor->setValue($resource, $key, $relation);

        return $resource;
    }
}
