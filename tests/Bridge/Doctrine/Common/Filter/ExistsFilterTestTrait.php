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
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait ExistsFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = $filter = $this->buildFilter(['name' => null, 'description' => null]);

        $this->assertEquals([
            'description[exists]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }
}
