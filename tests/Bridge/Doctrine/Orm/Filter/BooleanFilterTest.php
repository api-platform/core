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
use ApiPlatform\Core\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\BooleanFilterTestTrait;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class BooleanFilterTest extends DoctrineOrmFilterTestCase
{
    use BooleanFilterTestTrait;

    protected $filterClass = BooleanFilter::class;

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'string ("true")' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
                ],
                'string ("false")' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
                ],
                'non-boolean' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'numeric string ("0")' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
                ],
                'numeric string ("1")' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
                ],
                'nested properties' => [
                    sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.dummyBoolean = :dummyBoolean_p1', Dummy::class),
                ],
                'numeric string ("1") on non-boolean property' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'numeric string ("0") on non-boolean property' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'string ("true") on non-boolean property' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'string ("false") on non-boolean property' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'mixed boolean, non-boolean and invalid property' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyBoolean = :dummyBoolean_p1', Dummy::class),
                ],
            ]
        );
    }
}
