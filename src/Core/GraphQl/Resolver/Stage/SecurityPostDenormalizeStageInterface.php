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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

use GraphQL\Error\Error;

/**
 * Security post deserialization stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
interface SecurityPostDenormalizeStageInterface
{
    /**
     * @throws Error
     */
    public function __invoke(string $resourceClass, string $operationName, array $context): void;
}
