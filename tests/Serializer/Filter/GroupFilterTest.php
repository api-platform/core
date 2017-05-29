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

namespace ApiPlatform\Core\Tests\Serializer\Filter;

use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class GroupFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testApply()
    {
        $request = new Request(['groups' => ['foo', 'bar', 'baz']]);
        $context = ['groups' => ['foo', 'qux']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals(['groups' => ['foo', 'qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithOverriding()
    {
        $request = new Request(['custom_groups' => ['foo', 'bar', 'baz']]);
        $context = ['groups' => ['foo', 'qux']];

        $groupFilter = new GroupFilter('custom_groups', true);
        $groupFilter->apply($request, false, [], $context);

        $this->assertEquals(['groups' => ['foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithoutGroupsInRequest()
    {
        $context = ['groups' => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['groups' => ['foo', 'bar']], $context);
    }

    public function testApplyWithInvalidGroupsInRequest()
    {
        $request = new Request(['groups' => 'foo,bar,baz']);
        $context = ['groups' => ['foo', 'bar']];

        $groupFilter = new GroupFilter();
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals(['groups' => ['foo', 'bar']], $context);
    }

    public function testApplyWithInvalidGroupsInContext()
    {
        $request = new Request(['custom_groups' => ['foo', 'bar', 'baz']]);
        $context = ['groups' => 'qux'];

        $groupFilter = new GroupFilter('custom_groups');
        $groupFilter->apply($request, true, [], $context);

        $this->assertEquals(['groups' => ['qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testGetDescription()
    {
        $groupFilter = new GroupFilter('custom_groups');
        $expectedDescription = [
            'custom_groups[]' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
            ],
        ];

        $this->assertEquals($expectedDescription, $groupFilter->getDescription(DummyGroup::class));
    }
}
