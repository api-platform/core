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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class BooleanFilterTest extends AbstractFilterTest
{
    protected $filterClass = BooleanFilter::class;

    public function testGetDescription()
    {
        $filter = new BooleanFilter($this->managerRegistry, null, null, [
            'id' => null,
            'name' => null,
            'foo' => null,
            'dummyBoolean' => null,
        ]);

        $this->assertEquals([
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new BooleanFilter($this->managerRegistry);

        $this->assertEquals([
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return [
            'string ("true")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'true',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
            ],
            'string ("false")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'false',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
            ],
            'non-boolean' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'toto',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'numeric string ("0")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => '0',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
            ],
            'numeric string ("1")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => '1',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
            ],
            'nested properties' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.dummyBoolean' => null,
                ],
                [
                    'relatedDummy.dummyBoolean' => '1',
                ],
                sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.dummyBoolean = :dummyBoolean_p1', Dummy::class),
            ],
            'numeric string ("1") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                   'name' => '1',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'numeric string ("0") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'name' => '0',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'string ("true") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'name' => 'true',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'string ("false") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'name' => 'false',
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'mixed boolean, non-boolean and invalid property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'false',
                    'toto' => 'toto',
                    'name' => 'true',
                    'id' => '0',
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
            ],
        ];
    }
}
