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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\ExistsFilterTestTrait;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ExistsFilterTest extends DoctrineOrmFilterTestCase
{
    use ExistsFilterTestTrait;

    protected $filterClass = ExistsFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'id[exists]' => [
                'property' => 'id',
                'type' => 'bool',
                'required' => false,
            ],
            'alias[exists]' => [
                'property' => 'alias',
                'type' => 'bool',
                'required' => false,
            ],
            'description[exists]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
            'dummy[exists]' => [
                'property' => 'dummy',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyDate[exists]' => [
                'property' => 'dummyDate',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyFloat[exists]' => [
                'property' => 'dummyFloat',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyPrice[exists]' => [
                'property' => 'dummyPrice',
                'type' => 'bool',
                'required' => false,
            ],
            'jsonData[exists]' => [
                'property' => 'jsonData',
                'type' => 'bool',
                'required' => false,
            ],
            'arrayData[exists]' => [
                'property' => 'arrayData',
                'type' => 'bool',
                'required' => false,
            ],
            'nameConverted[exists]' => [
                'property' => 'nameConverted',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyBoolean[exists]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'valid values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                ],

                'valid values (empty for true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                ],

                'valid values (1 for true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                ],

                'invalid values' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],

                'negative values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
                ],

                'negative values (0)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
                ],

                'related values' => [
                    sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE o.description IS NOT NULL AND relatedDummy_a1.name IS NOT NULL', Dummy::class),
                ],

                'not nullable values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                ],

                'related collection not empty' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS NOT EMPTY', Dummy::class),
                ],

                'related collection empty' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS EMPTY', Dummy::class),
                ],

                'related association exists' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NOT NULL', Dummy::class),
                ],

                'related association does not exist' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NULL', Dummy::class),
                ],

                'related owned association does not exist' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedOwnedDummy relatedOwnedDummy_a1 WHERE relatedOwnedDummy_a1 IS NULL', Dummy::class),
                ],

                'related owned association exists' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedOwnedDummy relatedOwnedDummy_a1 WHERE relatedOwnedDummy_a1 IS NOT NULL', Dummy::class),
                ],

                'related owning association does not exist' => [
                    sprintf('SELECT o FROM %s o WHERE o.relatedOwningDummy IS NULL', Dummy::class),
                ],

                'related owning association exists' => [
                    sprintf('SELECT o FROM %s o WHERE o.relatedOwningDummy IS NOT NULL', Dummy::class),
                ],
            ]
        );
    }
}
