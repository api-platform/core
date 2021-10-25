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

namespace ApiPlatform\GraphQl\Resolver\Stage;

use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Security post validation stage of GraphQL resolvers.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class SecurityPostValidationStage implements SecurityPostValidationStageInterface
{
    private $resourceMetadataCollectionFactory;
    private $resourceAccessChecker;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ?ResourceAccessCheckerInterface $resourceAccessChecker)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->resourceAccessChecker = $resourceAccessChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(string $resourceClass, string $operationName, array $context): void
    {
        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $operation = $resourceMetadataCollection->getOperation($operationName);
        $isGranted = $operation->getSecurityPostValidation();

        if (null !== $isGranted && null === $this->resourceAccessChecker) {
            throw new \LogicException('Cannot check security expression when SecurityBundle is not installed. Try running "composer require symfony/security-bundle".');
        }

        if (null === $isGranted || $this->resourceAccessChecker->isGranted($resourceClass, (string) $isGranted, $context['extra_variables'])) {
            return;
        }

        throw new AccessDeniedHttpException($operation->getSecurityPostValidationMessage() ?? 'Access Denied.');
    }
}
