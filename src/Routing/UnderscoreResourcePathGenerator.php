<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Routing;

use Doctrine\Common\Inflector\Inflector;

/**
 * @author Paul Le Corre <paul@lecorre.me>
 */
class UnderscoreResourcePathGenerator implements ResourcePathGeneratorInterface
{
    public function generateResourceBasePath(string $resourceShortName) : string
    {
        $pathName = Inflector::tableize($resourceShortName);

        return Inflector::pluralize($pathName);
    }
}
