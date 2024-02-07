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

namespace ApiPlatform\Tests\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\SearchFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use Doctrine\Persistence\ManagerRegistry;
use MongoDB\BSON\Regex;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @group mongodb
 */
class SearchFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use ProphecyTrait;
    use SearchFilterTestTrait;

    protected string $filterClass = SearchFilter::class;
    protected string $resourceClass = Dummy::class;

    public function testGetDescriptionDefaultFields(): void
    {
        $filter = self::buildSearchFilter($this, $this->managerRegistry);

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
                'type' => \DateTimeInterface::class,
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'dummyDate[]' => [
                'property' => 'dummyDate',
                'type' => \DateTimeInterface::class,
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
            'name_converted' => [
                'property' => 'name_converted',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
            'name_converted[]' => [
                'property' => 'name_converted',
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

    public static function provideApplyTestData(): array
    {
        $filterFactory = self::buildSearchFilter(...);

        return array_merge_recursive(
            self::provideApplyTestArguments(),
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
                'exact (case insensitive, with special characters)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('^exact \(special\)$', 'i'),
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
                'partial (multiple values)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('CaSE'),
                                        new Regex('SENSitive'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'partial (multiple values; case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('CaSE', 'i'),
                                        new Regex('inSENSitive', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'partial (multiple almost same values; case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('blue car', 'i'),
                                        new Regex('Blue Car', 'i'),
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
                'start (multiple values)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('^CaSE'),
                                        new Regex('^SENSitive'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'start (multiple values; case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('^CaSE', 'i'),
                                        new Regex('^inSENSitive', 'i'),
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
                'end (multiple values)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('CaSE$'),
                                        new Regex('SENSitive$'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'end (multiple values; case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('CaSE$', 'i'),
                                        new Regex('inSENSitive$', 'i'),
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
                'word_start (multiple values)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('(^CaSE.*|.*\sCaSE.*)'),
                                        new Regex('(^SENSitive.*|.*\sSENSitive.*)'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'word_start (multiple values; case insensitive)' => [
                    [
                        [
                            '$match' => [
                                'name' => [
                                    '$in' => [
                                        new Regex('(^CaSE.*|.*\sCaSE.*)', 'i'),
                                        new Regex('(^inSENSitive.*|.*\sinSENSitive.*)', 'i'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'invalid value for relation' => [
                    [],
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
                'empty nested property' => [
                    [],
                    $filterFactory,
                ],
                'integer value' => [
                    [
                        [
                            '$match' => [
                                'age' => [
                                    '$in' => [
                                        '46',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                    RelatedDummy::class,
                ],
                'related owned one-to-one association' => [
                    [
                        [
                            '$match' => [
                                'relatedOwnedDummy' => [
                                    '$in' => [
                                        1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    $filterFactory,
                ],
                'related owning one-to-one association' => [
                    [
                        [
                            '$match' => [
                                'relatedOwningDummy' => [
                                    '$in' => [
                                        1,
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

    protected static function buildSearchFilter(self $that, ManagerRegistry $managerRegistry, ?array $properties = null): SearchFilter
    {
        $relatedDummyProphecy = $that->prophesize(RelatedDummy::class);
        $iriConverterProphecy = $that->prophesize(IriConverterInterface::class);

        $iriConverterProphecy->getResourceFromIri(Argument::type('string'), ['fetch_data' => false])->will(function ($args) use ($relatedDummyProphecy) {
            if (str_contains((string) $args[0], '/related_dummies')) {
                $relatedDummyProphecy->getId()->shouldBeCalled()->willReturn(1);

                return $relatedDummyProphecy->reveal();
            }

            throw new InvalidArgumentException();
        });

        $iriConverter = $iriConverterProphecy->reveal();
        $propertyAccessor = static::$kernel->getContainer()->get('test.property_accessor');

        return new SearchFilter($managerRegistry, $iriConverter, null, $propertyAccessor, null, $properties, new CustomConverter());
    }
}
