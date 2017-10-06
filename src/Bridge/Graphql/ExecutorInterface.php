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

namespace ApiPlatform\Core\Bridge\Graphql;

use GraphQL\Executor\ExecutionResult;

/**
 * Wrapper for the GraphQL facade.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
interface ExecutorInterface
{
    /**
     * @see http://webonyx.github.io/graphql-php/executing-queries/#using-facade-method
     *
     * @param array ...$args
     *
     * @return ExecutionResult
     */
    public function executeQuery(...$args): ExecutionResult;
}
