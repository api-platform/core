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

namespace ApiPlatform\Core\PathResolver;

/**
 * Resolves the path of a resource operation.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 */
interface OperationPathResolverInterface
{
    /**
     * Resolves the operation path.
     *
     * @param string $resourceShortName
     * @param array  $operation         The operation metadata
     * @param bool   $collection
     *
     * @return string
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, bool $collection): string;
}
