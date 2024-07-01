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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Loops over parameters to check parameter security.
 * Throws an exception if security is not granted.
 */
final class SecurityParameterProvider implements ProviderInterface
{
    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var Operation $apiOperation */
        $apiOperation = $context['request']->attributes->get('_api_operation');

        foreach ($apiOperation->getParameters() ?? [] as $parameter) {
            if (null === $security = $parameter->getSecurity()) {
                continue;
            }

            $apiValues = $parameter->getExtraProperties()['_api_values'] ?? [];
            if (!isset($apiValues[$parameter->getKey()])) {
                continue;
            }

            $parameterValue = $apiValues[$parameter->getKey()][0] ?? null;

            if (!$this->resourceAccessChecker->isGranted($context['resource_class'], $security, [$parameter->getKey() => $parameterValue])) {
                throw $operation instanceof GraphQlOperation ? new AccessDeniedHttpException($parameter->getSecurityMessage() ?? 'Access Denied.') : new AccessDeniedException($parameter->getSecurityMessage() ?? 'Access Denied.');
            }
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
