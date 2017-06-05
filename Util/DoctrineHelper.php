<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Util;

/**
 * Class DoctrineHelper
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class DoctrineHelper
{
    /**
     * @var integer
     */
    protected static $aliasCount = 0;

    /**
     * From an array of parameters name, generates and array for which the key is the parameter name and its value a
     * name generated from the parameter name and the prefix. This function goal is to generate parameters for the
     * queryBuilder which are specific enough to avoid any conflicts between parameters from a filter to another. The
     * more specific the prefix is, the safer the generated parameters are.
     *
     * @example
     *    ::secureParameters(['parameter1', 'parameter2'], 'my_prefix')
     *    // [
     *    'parameter1' => 'my_prefix_parameter1',
     *    'parameter2' => 'my_prefix_parameter2',
     *    ]
     *
     * @param array  $parameters
     * @param string $prefix
     *
     * @return array
     */
    public static function secureParameters(array $parameters, $prefix)
    {
        $securedParameters = [];

        if (false === empty($prefix)) {
            $prefix = sprintf('%s_', $prefix);
        }

        foreach ($parameters as $parameter) {
            $securedParameters[$parameter] = sprintf('%s%s_%s', $prefix, $parameter, self::$aliasCount);
            self::$aliasCount++;
        }

        return $securedParameters;
    }
}
