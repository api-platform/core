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

use ApiPlatform\Metadata\GraphQl\Operation;
use GraphQL\Error\Error;

/**
 * Validate stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface ValidateStageInterface
{
    /**
     * @param object $object
     *
     * @throws Error
     */
    public function __invoke($object, string $resourceClass, Operation $operation, array $context): void;
}
