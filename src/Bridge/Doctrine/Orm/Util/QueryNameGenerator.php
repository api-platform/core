<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class QueryNameGenerator implements QueryNameGeneratorInterface
{
    private $incrementedAssociation = 1;
    private $incrementedName = 1;

    /**
     * {@inheritdoc}
     */
    public function generateJoinAlias(string $association) : string
    {
        return sprintf('%s_a%d', $association, $this->incrementedAssociation++);
    }

    /**
     * {@inheritdoc}
     */
    public function generateParameterName(string $name) : string
    {
        return sprintf('%s_p%d', $name, $this->incrementedName++);
    }
}
