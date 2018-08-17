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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface as CommonQueryNameGeneratorInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
interface QueryNameGeneratorInterface extends CommonQueryNameGeneratorInterface
{
    /**
     * Generates a cacheable alias for DQL join.
     */
    public function generateJoinAlias(string $association): string;

    /**
     * Generates a cacheable parameter name for DQL query.
     */
    public function generateParameterName(string $name): string;
}
