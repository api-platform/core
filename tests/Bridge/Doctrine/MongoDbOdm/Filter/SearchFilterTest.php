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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\SearchFilter;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\SearchFilterTestTrait;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedDummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use MongoDB\BSON\Regex;
use Prophecy\Argument;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SearchFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use SearchFilterTestTrait;

    protected $filterClass = SearchFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildSearchFilter($this->managerRegistry);

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'name[]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'alias' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'alias[]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'description' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'description[]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummy' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummy[]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyDate' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyFloat' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyFloat[]' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'float',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'jsonData' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'jsonData[]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'arrayData' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'arrayData[]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'nameConverted' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'nameConverted[]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyBoolean[]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'relatedDummy' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'relatedDummy[]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'relatedDummies' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'relatedDummies[]' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'relatedOwnedDummy' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'relatedOwnedDummy[]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
            'relatedOwningDummy' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'relatedOwningDummy[]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        $filterFactory = [$this, 'buildSearchFilter'];

        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'exact' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        'exact',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'exact (case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('^exact$', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'exact (multiple values)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        'CaSE',
                                        'SENSitive',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'exact (multiple values; case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('^CaSE$', 'i'),
                                        new Regex('^inSENSitive$', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'invalid property' => [
                    [],
                    $filterFactory,
                ],
                'invalid values for relations' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        'foo',
                                    ],
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'relatedDummy' => [
                                    '$in' => [
                                        0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'partial' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('partial'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'partial (case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('partial', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'start' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('^partial'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'start (case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('^partial', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'end' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('partial$'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'end (case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('partial$', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'word_start' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('(^partial.*|.*\spartial.*)'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'word_start (case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('(^partial.*|.*\spartial.*)', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'invalid value for relation' => [
                    [
                        [
                            '$match' => [
                                'relatedDummy' => [
                                    '$in' => [
                                        0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'IRI value for relation' => [
                    [
                        [
                            '$lookup' => [
                                'from' => 'RelatedDummy',
                                'localField' => 'relatedDummy',
                                'foreignField' => '_id',
                                'as' => 'relatedDummy_lkup',
                            ],
                        ],
                        [
                            '$unwind' => '$relatedDummy_lkup',
                        ],
                        [
                            '$match' => [
                                'relatedDummy_lkup.id' => [
                                    '$in' => [
                                        1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'mixed IRI and entity ID values for relations' => [
                    [
                        [
                            '$match' => [
                                'relatedDummy' => [
                                    '$in' => [
                                        1,
                                        '2',
                                    ],
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'relatedDummies' => [
                                    '$in' => [
                                        '1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'nested property' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        'exact',
                                    ],
                                ],
                            ],
                        ],
                        [
                            '$lookup' => [
                                'from' => 'RelatedDummy',
                                'localField' => 'relatedDummy',
                                'foreignField' => '_id',
                                'as' => 'relatedDummy_lkup',
                            ],
                        ],
                        [
                            '$unwind' => '$relatedDummy_lkup',
                        ],
                        [
                            '$match' => [
                                'relatedDummy_lkup.symfony' => [
                                    '$in' => [
                                        'exact',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
            ]
        );
    }

    protected function buildSearchFilter(ManagerRegistry $managerRegistry, ?array $properties = null)
    {
        $relatedDummyProphecy = $this->prophesize(RelatedDummy::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $iriConverterProphecy->getItemFromIri(Argument::type('string'), ['fetch_data' => false])->will(function ($args) use ($relatedDummyProphecy) {
            if (false !== strpos($args[0], '/related_dummies')) {
                $relatedDummyProphecy->getId()->shouldBeCalled()->willReturn(1);

                return $relatedDummyProphecy->reveal();
            }

            throw new InvalidArgumentException();
        });

        $iriConverter = $iriConverterProphecy->reveal();
        $propertyAccessor = self::$kernel->getContainer()->get('test.property_accessor');

        return new SearchFilter($managerRegistry, $iriConverter, $propertyAccessor, null, $properties);
    }
}
