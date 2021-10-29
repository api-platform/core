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

namespace ApiPlatform\Tests\Core\Bridge\Rector\Service;

use ApiPlatform\Core\Bridge\Rector\Service\SubresourceTransformer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FourthLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Greeting;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use PHPUnit\Framework\TestCase;

class SubresourceTransformerTest extends TestCase
{
    /**
     * @dataProvider toUriVariablesProvider
     */
    public function testToUriVariables(array $metadata, array $expectedMetadata): void
    {
        $subresourceTransformer = new SubresourceTransformer();
        self::assertEquals($expectedMetadata, $subresourceTransformer->toUriVariables($metadata));
    }

    public function toUriVariablesProvider(): \Generator
    {
        yield '/questions/{id}/answer' => [
            [
                'property' => 'answer',
                'collection' => false,
                'resource_class' => Answer::class,
                'identifiers' => ['id' => [Question::class, 'id', true]],
                'path' => '/questions/{id}/answer.{_format}',
            ],
            [
                'id' => [
                    'from_class' => Question::class,
                    'from_property' => 'answer',
                    'to_class' => Answer::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];

        yield '/questions/{id}/answer/related_questions' => [
            //api_questions_answer_related_questions_get_subresource
            [
                'property' => 'relatedQuestions',
                'collection' => true,
                'resource_class' => Question::class,
                'identifiers' => [
                    'id' => [
                        Question::class,
                        'id',
                        true,
                    ],
                    'answer' => [
                        Answer::class,
                        'id',
                        false,
                    ],
                ],
                'path' => '/questions/{id}/answer/related_questions.{_format}',
            ],
            [
                'id' => [
                    'from_class' => Question::class,
                    'from_property' => 'answer',
                    'to_class' => Answer::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ],
                'answer' => [
                    'from_class' => Answer::class,
                    'from_property' => 'relatedQuestions',
                    'to_class' => Question::class,
                    'to_property' => null,
                    'identifiers' => [],
                    'composite_identifier' => false,
                    'expanded_value' => 'answer',
                ],
            ]
        ];

        yield '/dummies/{id}/related_dummies' => [
            //'api_dummies_related_dummies_get_subresource'
            [
                'property' => 'relatedDummies',
                'collection' => true,
                'resource_class' => RelatedDummy::class,
                'identifiers' => ['id' => [Dummy::class, 'id', true]],
                'path' => '/dummies/{id}/related_dummies.{_format}',
            ],
            [
                'id' => [
                    'from_class' => Dummy::class,
                    'from_property' => 'relatedDummies',
                    'to_class' => RelatedDummy::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];

        yield '/dummies/{id}/related_dummies/{relatedDummies}' => [
            //'api_dummies_related_dummies_item_get_subresource'
            [
                'property' => 'id',
                'collection' => false,
                'resource_class' => RelatedDummy::class,
                'identifiers' => [
                    'id' => [Dummy::class, 'id', true],
                    'relatedDummies' => [RelatedDummy::class, 'id', true]
                ],
                'path' => '/dummies/{id}/related_dummies/{relatedDummies}.{_format}',
            ],
            [
                'id' => [
                    'from_class' => Dummy::class,
                    'from_property' => 'relatedDummies',
                    'to_class' => RelatedDummy::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ],
                'relatedDummies' => [
                    'from_class' => RelatedDummy::class,
                    'from_property' => null,
                    'to_class' => RelatedDummy::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null
                ]
            ]
        ];

        yield '/dummies/{id}/related_dummies/{relatedDummies}/third_level' => [
            //api_dummies_related_dummies_item_third_level_get_subresource
            [
                'property' => 'thirdLevel',
                'collection' => false,
                'resource_class' => ThirdLevel::class,
                'identifiers' => [
                    'id' => [Dummy::class, 'id', true],
                    'relatedDummies' => [RelatedDummy::class, 'id', true]
                ],
                'path' => '/dummies/{id}/related_dummies/{relatedDummies}/third_level.{_format}'
            ],
            [
                'id' => [
                    'from_class' => Dummy::class,
                    'from_property' => 'relatedDummies',
                    'to_class' => RelatedDummy::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ],
                'relatedDummies' => [
                    'from_class' => RelatedDummy::class,
                    'from_property' => 'thirdLevel',
                    'to_class' => ThirdLevel::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];

         yield '/dummies/{id}/related_dummies/{relatedDummies}/third_level/fourth_level' => [
             //api_dummies_related_dummies_third_level_fourth_level_get_subresource
             [
                 'property' => 'fourthLevel',
                 'collection' => false,
                 'resource_class' => FourthLevel::class,
                 'identifiers' => [
                     'id' => [Dummy::class, 'id', true],
                     'relatedDummies' => [RelatedDummy::class, 'id', true],
                     'thirdLevel' => [ThirdLevel::class, 'id', false],
                 ],
                 'path' => '/dummies/{id}/related_dummies/{relatedDummies}/third_level/fourth_level.{_format}'
             ],
             [
                 'id' => [
                     'from_class' => Dummy::class,
                     'from_property' => 'relatedDummies',
                     'to_class' => RelatedDummy::class,
                     'to_property' => null,
                     'identifiers' => ['id'],
                     'composite_identifier' => false,
                     'expanded_value' => null,
                 ],
                 'relatedDummies' => [
                     'from_class' => RelatedDummy::class,
                     'from_property' => 'thirdLevel',
                     'to_class' => ThirdLevel::class,
                     'to_property' => null,
                     'identifiers' => ['id'],
                     'composite_identifier' => false,
                     'expanded_value' => null,
                 ],
                 'thirdLevel' => [
                     'from_class' => ThirdLevel::class,
                     'from_property' => 'fourthLevel',
                     'to_class' => FourthLevel::class,
                     'to_property' => null,
                     'identifiers' => [],
                     'composite_identifier' => false,
                     'expanded_value' => 'third_level',
                 ]
             ]
         ];

        yield '/dummy_products/{id}/offers/{offers}/offers' => [
            [
                'property' => 'offers',
                'collection' => true,
                'resource_class' => DummyAggregateOffer::class,
                'identifiers' => [
                    'id' => [
                        DummyProduct::class,
                        'id',
                        true
                    ]
                ],
                'path' => '/dummy_products/{id}/offers/{offers}/offers.{_format}'
            ],
            [
                'id' => [
                    'from_class' => DummyProduct::class,
                    'to_property' => 'product',
                    'to_class' => null,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ],
                'offers' => [
                    'from_class' => DummyAggregateOffer::class,
                    'to_property' => 'aggregate',
                    'to_class' => null,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];

        yield '/dummy_aggregate_offers/{id}/offers' => [
            [
                'property' => 'offers',
                'collection' => true,
                'resource_class' => DummyOffer::class,
                'identifiers' => [
                    'id' => [DummyAggregateOffer::class, 'id', true]
                ],
                'path' => '/dummy_aggregate_offers/{id}/offers.{_format}'
            ],
            [
                'id' => [
                    'from_class' => DummyAggregateOffer::class,
                    'from_property' => 'offers',
                    'to_class' => DummyOffer::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];

        yield '/people/{id}/sent_greetings' => [
            [
                'property' => 'sentGreetings',
                'collection' => true,
                'resource_class' => Greeting::class,
                'identifiers' => [
                    'id' => [Person::class, 'id', true]
                ],
                'path' => '/people/{id}/sent_greetings.{_format}'
            ],
            [
                'id' => [
                    'from_class' => Person::class,
                    'from_property' => 'sentGreetings',
                    'to_class' => Greeting::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];

        yield '/related_owned_dummies/{id}/owning_dummy' => [
            [
                'property' => 'owningDummy',
                'collection' => false,
                'resource_class' => Dummy::class,
                'identifiers' => [
                    'id' => [RelatedOwnedDummy::class, 'id', true]
                ],
                'path' => '/related_owned_dummies/{id}/owned_dummy.{_format}'
            ],
            [
                'id' => [
                    'from_class' => RelatedOwnedDummy::class,
                    'from_property' => null,
                    'to_class' => Dummy::class,
                    'to_property' => 'owningDummy',
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];

        yield '/related_owning_dummies/{id}/owned_dummy' => [
            [
                'property' => 'ownedDummy',
                'collection' => false,
                'resource_class' => Dummy::class,
                'identifiers' => [
                    'id' => [RelatedOwningDummy::class, 'id', true]
                ],
                'path' => '/related_owning_dummies/{id}/owned_dummy.{_format}'
            ],
            [
                'id' => [
                    'from_class' => RelatedOwningDummy::class,
                    'from_property' => 'ownedDummy',
                    'to_class' => Dummy::class,
                    'to_property' => null,
                    'identifiers' => ['id'],
                    'composite_identifier' => false,
                    'expanded_value' => null,
                ]
            ]
        ];
    }
}
