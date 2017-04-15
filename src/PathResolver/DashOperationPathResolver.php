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

use Doctrine\Common\Inflector\Inflector;

/**
 * Generates a path with words separated by dashes.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 */
final class DashOperationPathResolver implements OperationPathResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, bool $collection): string
    {
        $path = '/'.Inflector::pluralize(strtolower(preg_replace('~(?<=\\w)([A-Z])~', '-$1', $resourceShortName)));

        if (!$collection) {
            $path .= '/{id}';
        }

        $path .= '.{_format}';

        return $path;
    }
}
