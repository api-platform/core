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

namespace ApiPlatform\Core\Tests\Doctrine\Util;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class QueryNameGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateJoinAlias()
    {
        $queryNameGenerator = new QueryNameGenerator();
        $this->assertEquals('related_a1', $queryNameGenerator->generateJoinAlias('related'));
    }

    public function testGenerateParemeterName()
    {
        $queryNameGenerator = new QueryNameGenerator();
        $this->assertEquals('name_p1', $queryNameGenerator->generateParameterName('name'));
    }
}
