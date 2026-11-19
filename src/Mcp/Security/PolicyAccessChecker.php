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

namespace ApiPlatform\Mcp\Security;

use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;

/**
 * Evaluates the operation-level "policy", as used by the Laravel integration.
 *
 * @experimental
 */
final class PolicyAccessChecker implements ElementAccessCheckerInterface
{
    public function __construct(
        private readonly OperationMetadataFactoryInterface $operationMetadataFactory,
        private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null,
    ) {
    }

    public function isGranted(string $operationName): bool
    {
        if (null === $this->resourceAccessChecker) {
            return true;
        }

        $operation = $this->operationMetadataFactory->create($operationName);

        if (null === $operation || null === ($policy = $operation->getPolicy())) {
            return true;
        }

        try {
            return $this->resourceAccessChecker->isGranted($operation->getClass() ?? '', $policy, []);
        } catch (\ArgumentCountError) {
            // Gate::callPolicyMethod shifts off the policy name and calls $policy->{$method}($user),
            // so a policy method that requires a model instance throws instead of answering. Listing
            // cannot decide, so the element stays visible and the policy is enforced on tools/call
            // and resources/read by AccessCheckerProvider.
            return true;
        }
    }
}
