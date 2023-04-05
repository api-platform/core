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

namespace ApiPlatform\Doctrine\Orm\Tests\Util;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class QueryNameGeneratorTest extends TestCase
{
    public function testGenerateJoinAlias(): void
    {
        $queryNameGenerator = new QueryNameGenerator();
        $this->assertSame('related_a1', $queryNameGenerator->generateJoinAlias('related'));
    }

    public function testGenerateParameterName(): void
    {
        $queryNameGenerator = new QueryNameGenerator();
        $this->assertSame('name_p1', $queryNameGenerator->generateParameterName('name'));
    }
}
