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
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait ExistsFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = new $this->filterClass($this->managerRegistry, null, null, ['name' => null, 'description' => null]);

        $this->assertEquals([
            'description[exists]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new $this->filterClass($this->managerRegistry);

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
}
