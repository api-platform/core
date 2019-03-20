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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Tests\Fixtures\DummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ApiFilterTest extends TestCase
{
    public function testInvalidConstructor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This annotation needs a value representing the filter class.');

        new ApiFilter();
    }

    public function testInvalidFilter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The filter class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy" does not implement "ApiPlatform\\Core\\Api\\FilterInterface".');

        new ApiFilter(['value' => Dummy::class]);
    }

    public function testInvalidProperty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "foo" does not exist on the ApiFilter annotation.');

        new ApiFilter(['value' => DummyFilter::class, 'foo' => 'bar']);
    }

    public function testAssignation()
    {
        $resource = new ApiFilter(['value' => DummyFilter::class, 'strategy' => 'test', 'properties' => ['one', 'two'], 'arguments' => ['args']]);

        $this->assertEquals($resource->filterClass, DummyFilter::class);
        $this->assertEquals($resource->strategy, 'test');
        $this->assertEquals($resource->properties, ['one', 'two']);
        $this->assertEquals($resource->arguments, ['args']);
    }
}
