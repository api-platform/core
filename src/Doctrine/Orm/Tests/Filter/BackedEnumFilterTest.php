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

namespace ApiPlatform\Doctrine\Orm\Tests\Filter;

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Tests\DoctrineOrmFilterTestCase;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;

/**
 * @author Rémi Marseille <marseille.remi@gmail.com>
 */
final class BackedEnumFilterTest extends DoctrineOrmFilterTestCase
{
    use BackedEnumFilterTestTrait;

    protected string $filterClass = BackedEnumFilter::class;

    public static function provideApplyTestData(): array
    {
        return array_merge_recursive(
            self::provideApplyTestArguments(),
            [
                'valid case' => [
                    \sprintf('SELECT o FROM %s o WHERE o.dummyBackedEnum = :dummyBackedEnum_p1', Dummy::class),
                ],
                'invalid case' => [
                    \sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'valid case for nested property' => [
                    \sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.dummyBackedEnum = :dummyBackedEnum_p1', Dummy::class),
                ],
                'invalid case for nested property' => [
                    \sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'valid case (multiple values)' => [
                    \sprintf('SELECT o FROM %s o WHERE o.dummyBackedEnum IN (:dummyBackedEnum_p1)', Dummy::class),
                    [
                        'dummyBackedEnum_p1' => [
                            'one',
                            'two',
                        ],
                    ],
                ],
            ]
        );
    }
}
