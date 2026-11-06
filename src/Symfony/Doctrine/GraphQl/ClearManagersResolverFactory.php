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

namespace ApiPlatform\Symfony\Doctrine\GraphQl;

use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Doctrine\Persistence\ManagerRegistry;

final class ClearManagersResolverFactory implements ResolverFactoryInterface
{
    public function __construct(
        private readonly ResolverFactoryInterface $decorated,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?Operation $operation = null, ?PropertyMetadataFactoryInterface $propertyMetadataFactory = null): callable
    {
        $resolver = ($this->decorated)($resourceClass, $rootClass, $operation, $propertyMetadataFactory);

        if (!$operation instanceof Mutation) {
            return $resolver;
        }

        return function (...$args) use ($resolver) {
            try {
                return $resolver(...$args);
            } catch (\Throwable $e) {
                foreach ($this->managerRegistry->getManagers() as $manager) {
                    $manager->clear();
                }

                throw $e;
            }
        };
    }
}
