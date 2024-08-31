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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;

/**
 * Checks the individual parts of the linked resource for access rights.
 *
 * @experimental
 */
final class LinkAccessCheckerProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $decorated,
        private readonly ResourceAccessCheckerInterface $resourceAccessChecker,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = ($context['request'] ?? null);

        $data = $this->decorated->provide($operation, $uriVariables, $context);

        if ($operation instanceof HttpOperation && $operation->getUriVariables()) {
            foreach ($operation->getUriVariables() as $uriVariable) {
                if (!$uriVariable instanceof Link || !$uriVariable->getSecurity()) {
                    continue;
                }

                $targetResource = $uriVariable->getFromClass() ?? $uriVariable->getToClass();

                if (!$targetResource) {
                    continue;
                }

                $propertyName = $uriVariable->getToProperty() ?? $uriVariable->getFromProperty();
                $securityObjectName = $uriVariable->getSecurityObjectName();

                if (!$securityObjectName) {
                    $securityObjectName = $propertyName;
                }

                if (!$securityObjectName) {
                    continue;
                }

                $resourceAccessCheckerContext = [
                    'object' => $data,
                    'previous_object' => $request?->attributes->get('previous_data'),
                    $securityObjectName => $request?->attributes->get($securityObjectName),
                    'request' => $request,
                ];

                if (!$this->resourceAccessChecker->isGranted($targetResource, $uriVariable->getSecurity(), $resourceAccessCheckerContext)) {
                    throw new AccessDeniedException($uriVariable->getSecurityMessage() ?? 'Access Denied.');
                }
            }
        }

        return $data;
    }
}
