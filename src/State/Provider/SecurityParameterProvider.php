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
use ApiPlatform\State\Util\ParameterParserTrait;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Loops over parameters to check parameter security.
 * Throws an exception if security is not granted.
 */
final class SecurityParameterProvider implements ProviderInterface
{
    use ParameterParserTrait;

    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!($request = $context['request']) instanceof Request) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        /** @var Operation $apiOperation */
        $apiOperation = $request->attributes->get('_api_operation');

        foreach ($apiOperation->getParameters() ?? [] as $parameter) {
            if (null === $security = $parameter->getSecurity()) {
                continue;
            }

            $key = $this->getParameterFlattenKey($parameter->getKey(), $this->extractParameterValues($parameter, $request, $context));
            $apiValues = $parameter->getExtraProperties()['_api_values'] ?? [];
            if (!isset($apiValues[$key])) {
                continue;
            }
            $value = $apiValues[$key];

            if (!$this->resourceAccessChecker->isGranted($context['resource_class'], $security, [$key => $value])) {
                throw $operation instanceof GraphQlOperation ? new AccessDeniedHttpException($parameter->getSecurityMessage() ?? 'Access Denied.') : new AccessDeniedException($parameter->getSecurityMessage() ?? 'Access Denied.');
            }
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
