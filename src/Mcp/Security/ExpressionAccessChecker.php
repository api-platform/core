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
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Evaluates the operation-level "security" expression, as used by the Symfony integration.
 *
 * @experimental
 */
final class ExpressionAccessChecker implements ElementAccessCheckerInterface
{
    public function __construct(
        private readonly OperationMetadataFactoryInterface $operationMetadataFactory,
        private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null,
        private readonly ?RequestStack $requestStack = null,
    ) {
    }

    public function isGranted(string $operationName): bool
    {
        if (null === $this->resourceAccessChecker) {
            return true;
        }

        $operation = $this->operationMetadataFactory->create($operationName);

        if (null === $operation || null === ($security = $operation->getSecurity())) {
            return true;
        }

        try {
            return $this->resourceAccessChecker->isGranted($operation->getClass() ?? '', $security, ['request' => $this->requestStack?->getCurrentRequest()]);
        } catch (SyntaxError) {
            // The expression reads variables that only exist once the element is called (object,
            // previous_object, uri variables). Listing cannot decide, so the element stays visible
            // and the expression is enforced on tools/call and resources/read, as
            // AccessCheckerProvider already defers the pre_read stage in that case.
            return true;
        }
    }
}
