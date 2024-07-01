<?php

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\State\ProviderInterface;

/**
 * Loops over parameters to check parameter security
 * Operation's parameters are set to only
 * - parameters without security
 * - parameters with security granted
 */
final class SecurityParameterProvider implements ProviderInterface
{
    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null,)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $parameters = $operation->getParameters() ?? [];
        $operationParameters = $parameters instanceof Parameters ? iterator_to_array($parameters) : $parameters;

        $securedOperationParameters = new Parameters();
        foreach ($operationParameters as $parameter) {
            if (null === $security = $parameter->getSecurity()) {
                $securedOperationParameters->add($parameter->getKey(), $parameter);
                continue;
            }

            if ($this->resourceAccessChecker->isGranted($context['resource_class'], $security)) {
                $securedOperationParameters->add($parameter->getKey(), $parameter);
            }
        }

        $operation = $operation->withParameters($securedOperationParameters);

        $context['request']?->attributes->set('_api_operation', $operation);
        $context['operation'] = $operation;

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
