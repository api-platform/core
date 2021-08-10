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

namespace ApiPlatform\Core\Tests\Annotation;

use ApiPlatform\Core\Annotation\ApiSubresource;
use PHPUnit\Framework\TestCase;

/**
 * @author Cody Banman <crbanman@gmail.com>
 */
class ApiSubresourceTest extends TestCase
{
    public function testAssignation()
    {
        $property = new ApiSubresource();
        $property->maxDepth = 1;

        $this->assertEquals(1, $property->maxDepth);
    }

    public function testConstruct()
    {
        $property = new ApiSubresource([ // @phpstan-ignore-line
            'maxDepth' => 1,
        ]);
        $this->assertEquals(1, $property->maxDepth);
    }

    /**
     * @requires PHP 8.0
     */
    public function testConstructAttribute()
    {
        $property = eval(<<<'PHP'
return new \ApiPlatform\Core\Annotation\ApiSubresource(
    maxDepth: 1
);
PHP
        );
        $this->assertEquals(1, $property->maxDepth);
    }
}
