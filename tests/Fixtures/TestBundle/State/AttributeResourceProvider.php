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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;

class AttributeResourceProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AttributeResources|AttributeResource
    {
        if (isset($uriVariables['identifier'])) {
            $resource = new AttributeResource((int) $uriVariables['identifier'], 'Foo');

            if ($uriVariables['dummyId'] ?? false) {
                $resource->dummy = new Dummy();
                $resource->dummy->setId((int) $uriVariables['dummyId']);
            }

            return $resource;
        }

        return new AttributeResources(new AttributeResource(1, 'Foo'), new AttributeResource(2, 'Bar'));
    }
}
