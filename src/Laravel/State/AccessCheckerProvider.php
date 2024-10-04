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

namespace ApiPlatform\Laravel\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Allows access based on the ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface.
 * This implementation covers GraphQl and HTTP.
 *
 * @see ResourceAccessCheckerInterface
 *
 * @implements ProviderInterface<object>
 */
final class AccessCheckerProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<object> $decorated
     */
    public function __construct(private readonly ProviderInterface $decorated, private readonly ResourceAccessCheckerInterface $resourceAccessChecker)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $policy = $operation->getPolicy();
        $message = $operation->getSecurityMessage();

        $body = $this->decorated->provide($operation, $uriVariables, $context);
        if (null === $policy) {
            return $body;
        }

        $request = $context['request'] ?? null;

        $resourceAccessCheckerContext = [
            'object' => $body,
            'request' => $request,
            'operation' => $operation,
        ];

        if (!$this->resourceAccessChecker->isGranted($operation->getClass(), $policy, $resourceAccessCheckerContext)) {
            throw $operation instanceof HttpOperation ? new AuthorizationException($message ?? 'Access Denied.') : new AccessDeniedHttpException($message ?? 'Access Denied.');
        }

        return $body;
    }
}
