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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

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
            'exists[id]' => [
                'property' => 'id',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[alias]' => [
                'property' => 'alias',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[description]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummy]' => [
                'property' => 'dummy',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyFloat]' => [
                'property' => 'dummyFloat',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyPrice]' => [
                'property' => 'dummyPrice',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[jsonData]' => [
                'property' => 'jsonData',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[arrayData]' => [
                'property' => 'arrayData',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[name_converted]' => [
                'property' => 'name_converted',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        $existsFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null, RequestStack $requestStack = null): ExistsFilter {
            return new ExistsFilter($managerRegistry, $requestStack, null, $properties, 'exists');
        };
        $customExistsFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null, RequestStack $requestStack = null): ExistsFilter {
            return new ExistsFilter($managerRegistry, $requestStack, null, $properties, 'customExists');
        };

        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'valid values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'valid values (empty for true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'valid values (1 for true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'invalid values' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'negative values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'negative values (0)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'multiple values (true and true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.alias IS NOT NULL AND o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'multiple values (1 and 0)' => [
                    sprintf('SELECT o FROM %s o WHERE o.alias IS NOT NULL AND o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'multiple values (false and 0)' => [
                    sprintf('SELECT o FROM %s o WHERE o.alias IS NULL AND o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'custom exists parameter name' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $customExistsFilterFactory,
                ],

                'related values' => [
                    sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE o.description IS NOT NULL AND relatedDummy_a1.name IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'not nullable values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related collection not empty' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS NOT EMPTY', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related collection empty' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS EMPTY', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related association exists' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related association does not exist' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owned association does not exist' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedOwnedDummy relatedOwnedDummy_a1 WHERE relatedOwnedDummy_a1 IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owned association exists' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedOwnedDummy relatedOwnedDummy_a1 WHERE relatedOwnedDummy_a1 IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owning association does not exist' => [
                    sprintf('SELECT o FROM %s o WHERE o.relatedOwningDummy IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owning association exists' => [
                    sprintf('SELECT o FROM %s o WHERE o.relatedOwningDummy IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],
            ]
        );
    }

    /**
     * @group legacy
     * @expectedDeprecation The ExistsFilter syntax "description[exists]=true/false" is deprecated since 2.5. Use the syntax "exists[description]=true/false" instead.
     */
    public function testLegacyExistsAfterSyntax()
    {
        $args = [
            [
                'description' => null,
            ],
            [
                'description' => [
                    'exists' => 'true',
                ],
            ],
            sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
            null,
            function (ManagerRegistry $managerRegistry, array $properties = null, RequestStack $requestStack = null): ExistsFilter {
                return new ExistsFilter($managerRegistry, $requestStack, null, $properties, 'exists');
            },
        ];

        $this->testApply(...$args);
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpFoundation\RequestStack" is deprecated since 2.2. Use "filters" context key instead.
     * @expectedDeprecation Using "ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter::apply()" is deprecated since 2.2. Use "ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter::apply()" with the "filters" context key instead.
     * @expectedDeprecation The use of "ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter::extractProperties()" is deprecated since 2.2. Use the "filters" key of the context instead.
     * @expectedDeprecation The ExistsFilter syntax "description[exists]=true/false" is deprecated since 2.5. Use the syntax "exists[description]=true/false" instead.
     */
    public function testLegacyRequest()
    {
        $args = [
            [
                'description' => null,
            ],
            [
                'description' => [
                    'exists' => 'true',
                ],
            ],
            sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
            null,
            function (ManagerRegistry $managerRegistry, array $properties = null, RequestStack $requestStack = null): ExistsFilter {
                return new ExistsFilter($managerRegistry, $requestStack, null, $properties, 'exists', new CustomConverter());
            },
        ];

        $this->testApplyRequest(...$args);
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, null, $properties, 'exists', new CustomConverter());
    }
}
