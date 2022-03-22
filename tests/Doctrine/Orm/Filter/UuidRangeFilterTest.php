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

namespace ApiPlatform\Tests\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\UuidRangeFilter;
use ApiPlatform\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\UuidRangeFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyUuidV6;

class UuidRangeFilterTest extends DoctrineOrmFilterTestCase
{
    use UuidRangeFilterTestTrait;

    protected $filterClass = UuidRangeFilter::class;
    protected $resourceClass = DummyUuidV6::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();
        $this->assertEquals([
            'id[between]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'between' => [
                    sprintf('SELECT o FROM %s o WHERE o.id BETWEEN :id_p1_1 AND :id_p1_2', Dummy::class),
                ],
                'between (same values)' => [
                    sprintf('SELECT o FROM %s o WHERE o.id = :id_p1', Dummy::class),
                ],
                'between (too many operands)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'between (too few operands)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'between (non-uuid operands)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'lt' => [
                    sprintf('SELECT o FROM %s o WHERE o.id < :id_p1', Dummy::class),
                ],
                'lt (non-uuid)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'lte' => [
                    sprintf('SELECT o FROM %s o WHERE o.id <= :id_p1', Dummy::class),
                ],
                'lte (non-uuid)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'gt' => [
                    sprintf('SELECT o FROM %s o WHERE o.id > :id_p1', Dummy::class),
                ],
                'gt (non-uuid)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'gte' => [
                    sprintf('SELECT o FROM %s o WHERE o.id >= :id_p1', Dummy::class),
                ],
                'gte (non-uuid)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'lte + gte' => [
                    sprintf('SELECT o FROM %s o WHERE o.id >= :id_p1 AND o.id <= :id_p2', Dummy::class),
                ],
            ]
        );
    }
}
