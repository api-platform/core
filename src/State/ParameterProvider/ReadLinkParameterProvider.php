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

namespace ApiPlatform\State\ParameterProvider;

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Checks if the linked resources have security attributes and prepares them for access checking.
 *
 * @experimental
 */
final class ReadLinkParameterProvider implements ParameterProviderInterface
{
    /**
     * @param ProviderInterface<object> $locator
     */
    public function __construct(
        private readonly ProviderInterface $locator,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $operation = $context['operation'];
        $extraProperties = $parameter->getExtraProperties();

        if ($parameter instanceof Link) {
            $linkClass = $parameter->getFromClass() ?? $parameter->getToClass();
            $securityObjectName = $parameter->getSecurityObjectName() ?? $parameter->getToProperty() ?? $parameter->getFromProperty();
        }

        $securityObjectName ??= $parameter->getKey();

        $linkClass ??= $extraProperties['resource_class'] ?? $operation->getClass();

        if (!$linkClass) {
            return $operation;
        }

        $linkOperation = $this->resourceMetadataCollectionFactory
            ->create($linkClass)
            ->getOperation($operation->getExtraProperties()['parent_uri_template'] ?? $extraProperties['uri_template'] ?? null);

        $value = $parameter->getValue();

        if (\is_array($value) && array_is_list($value)) {
            $relation = [];

            foreach ($value as $v) {
                try {
                    $relation[] = $this->locator->provide($linkOperation, $this->getUriVariables($v, $parameter, $linkOperation), $context);
                } catch (ProviderNotFoundException) {
                }
            }
        } else {
            try {
                $relation = $this->locator->provide($linkOperation, $this->getUriVariables($value, $parameter, $linkOperation), $context);
            } catch (ProviderNotFoundException) {
                $relation = null;
            }
        }

        $parameter->setValue($relation);

        if (null === $relation && true === ($extraProperties['throw_not_found'] ?? true)) {
            throw new NotFoundHttpException('Relation for link security not found.');
        }

        $context['request']?->attributes->set($securityObjectName, $relation);

        if ($parameter instanceof Link) {
            $uriVariables = $operation->getUriVariables();
            $uriVariables[$parameter->getKey()] = $parameter;
            $operation = $operation->withUriVariables($uriVariables);
        }

        return $operation;
    }

    /**
     * @return array<string, string>
     */
    private function getUriVariables(mixed $value, Parameter $parameter, Operation $operation): array
    {
        $extraProperties = $parameter->getExtraProperties();

        if ($operation instanceof HttpOperation) {
            $links = $operation->getUriVariables();
        } elseif ($operation instanceof GraphQlOperation) {
            $links = $operation->getLinks();
        } else {
            $links = [];
        }

        if (!\is_array($value)) {
            $uriVariables = [];

            foreach ($links as $key => $link) {
                if (!\is_string($key)) {
                    $key = $link->getParameterName() ?? $extraProperties['uri_variable'] ?? $link->getFromProperty();
                }

                if (!$key || !\is_string($key)) {
                    continue;
                }

                $uriVariables[$key] = $value;
            }

            return $uriVariables;
        }

        return $value;
    }
}
