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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
trait DateFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'dummyDate[before]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[strictly_before]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[after]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[strictly_after]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }
}
