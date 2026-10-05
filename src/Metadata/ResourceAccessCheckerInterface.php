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

namespace ApiPlatform\Metadata;

/**
 * Checks if the current user can access a resource, with a security expression.
 */
interface ResourceAccessCheckerInterface
{
    /**
     * Checks if the given item can be accessed by the current user.
     *
     * @param array{object?: mixed, previous_object?: mixed, request?: \Symfony\Component\HttpFoundation\Request} $extraVariables
     */
    public function isGranted(string $resourceClass, string $expression, array $extraVariables = []): bool;
}
