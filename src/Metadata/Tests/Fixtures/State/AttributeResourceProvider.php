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

namespace ApiPlatform\Metadata\Tests\Fixtures\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResource;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResources;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\State\ProviderInterface;

class AttributeResourceProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AttributeResources|AttributeResource
    {
        if (isset($uriVariables['identifier'])) {
            $resource = new AttributeResource($uriVariables['identifier'], 'Foo');

            if ($uriVariables['dummyId'] ?? false) {
                $resource->dummy = new Dummy();
                $resource->dummy->setId($uriVariables['dummyId']);
            }

            return $resource;
        }

        return new AttributeResources(new AttributeResource(1, 'Foo'), new AttributeResource(2, 'Bar'));
    }
}
