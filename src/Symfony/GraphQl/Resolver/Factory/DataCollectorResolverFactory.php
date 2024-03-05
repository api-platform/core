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

namespace ApiPlatform\Symfony\GraphQl\Resolver\Factory;

use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\HttpFoundation\RequestStack;

final class DataCollectorResolverFactory implements ResolverFactoryInterface
{
    public function __construct(private readonly ResolverFactoryInterface $resolverFactory, private readonly ?RequestStack $requestStack)
    {
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?Operation $operation = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operation) {
            if ($this->requestStack && null !== $request = $this->requestStack->getCurrentRequest()) {
                $request->attributes->set(
                    '_graphql_args',
                    [$resourceClass => $args] + $request->attributes->get('_graphql_args', [])
                );
            }

            return ($this->resolverFactory)($resourceClass, $rootClass, $operation)($source, $args, $context, $info);
        };
    }
}
