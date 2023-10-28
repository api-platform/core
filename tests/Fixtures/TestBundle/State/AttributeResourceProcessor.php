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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;

class AttributeResourceProcessor
{
    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AttributeResource
    {
        $dummy = new Dummy();
        $dummy->setId(1);
        $a = new AttributeResource(2, 'Patched');
        $a->dummy = $dummy;

        return $a;
    }
}
