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
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
trait BooleanFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = new $this->filterClass($this->managerRegistry, null, null, [
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
        $filter = new $this->filterClass($this->managerRegistry);

        $this->assertEquals([
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }
}
