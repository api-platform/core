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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Operation;


trait DeprecateWrongIriConversionTrait {
    public function getIriConverterContextWithDifferentClasses(string $resourceClass, string $expectedClass, array $context)
    {
        if (!$this->resourceMetadataFactory || !$this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            return $context;
        }

        // TODO: add documentation link
        @trigger_error('You are trying to serialize the class "%s" but we expected to "%s" instead. This behavior will not be possible anymore in 3.0. Possible reasons: use of a Resource on an Abstract class, returning the wrong Resource in a controller or a Data Provider. Replace this behavior using an Alternate Route.', E_USER_DEPRECATED);
        
        [, $operation] = $this->resourceMetadataFactory->create($resourceClass)->getFirstOperation();

        if (!$operation) {
            return $context;
        }

        $context['links'] = $operation->getLinks();
        return $context;
    }
}
