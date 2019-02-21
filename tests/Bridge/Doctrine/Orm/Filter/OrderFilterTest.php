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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\OrderFilterTestTrait;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class OrderFilterTest extends DoctrineOrmFilterTestCase
{
    use OrderFilterTestTrait;

    protected $filterClass = OrderFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'order[alias]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'order[description]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummy]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyFloat]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyPrice]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'order[jsonData]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'order[arrayData]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
            ],
            'order[nameConverted]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        $orderFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null, RequestStack $requestStack = null): OrderFilter {
            return new OrderFilter($managerRegistry, $requestStack, 'order', null, $properties);
        };
        $customOrderFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null, RequestStack $requestStack = null): OrderFilter {
            return new OrderFilter($managerRegistry, $requestStack, 'customOrder', null, $properties);
        };

        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'valid values' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid values' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'valid values (properties not enabled)' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid values (properties not enabled)' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.name ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid property (property not enabled)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid property (property enabled)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'custom order parameter name' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.name DESC', Dummy::class),
                    null,
                    $customOrderFilterFactory,
                ],
                'valid values (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nested property' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedDummy relatedDummy_a1 ORDER BY o.id ASC, o.name DESC, relatedDummy_a1.symfony DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'empty values with default sort direction' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_smallest (asc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_smallest (desc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate DESC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_largest (asc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_largest (desc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate DESC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'not having order should not throw a deprecation (select unchanged)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'not nullable relation will be a LEFT JOIN' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedDummy relatedDummy_a1 ORDER BY relatedDummy_a1.name ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
            ]
        );
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, 'order', null, $properties);
    }
}
