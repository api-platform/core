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

/**
 * @author Paul Le Corre <paul@lecorre.me>
 */
interface ResourcePathGeneratorInterface
{
    public function generateResourceBasePath(string $resourceShortName) : string;
}
