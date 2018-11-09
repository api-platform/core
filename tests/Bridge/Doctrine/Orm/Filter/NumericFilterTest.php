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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\NumericFilterTestTrait;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class NumericFilterTest extends DoctrineOrmFilterTestCase
{
    use NumericFilterTestTrait;

    protected $filterClass = NumericFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => false,
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => true,
            ],
            'dummyFloat' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'is_collection' => false,
            ],
            'dummyFloat[]' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'is_collection' => true,
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'is_collection' => false,
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'is_collection' => true,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'numeric string (positive integer)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
                ],
                'multiple numeric string (positive integer)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyPrice IN (:dummyPrice_p1)', Dummy::class),
                ],
                'multiple numeric string with one invalid property key' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
                ],
                'multiple numeric string with invalid value keys' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'multiple non-numeric' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'numeric string (negative integer)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
                ],
                'non-numeric' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'numeric string ("0")' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
                ],
                'nested property' => [
                    sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.id = :id_p1', Dummy::class),
                ],
                'mixed numeric and non-numeric' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
                ],
                'mixed numeric, non-numeric and invalid property' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyPrice = :dummyPrice_p1', Dummy::class),
                ],
            ]
        );
    }
}
