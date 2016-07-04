<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Naming;

use Doctrine\Common\Inflector\Inflector;

/**
 * Generates a path with words separated by dashes.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 */
final class DashResourcePathNamingStrategy implements ResourcePathNamingStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateResourceBasePath(string $resourceShortName) : string
    {
        return Inflector::pluralize(strtolower(preg_replace('~(?<=\\w)([A-Z])~', '-$1', $resourceShortName)));
    }
}
