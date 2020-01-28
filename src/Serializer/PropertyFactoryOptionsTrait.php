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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Manipulates the property factory options.
 *
 * @internal
 *
 * @author @author Alan Poulain <contact@alanpoulain.eu>
 */
trait PropertyFactoryOptionsTrait
{
    /**
     * Gets the options for a property factory.
     */
    private function getPropertyFactoryOptions(string $resourceClass, bool $splitDeserialization = false): array
    {
        try {
            $request = Request::createFromGlobals();
            $serializationContext = $this->serializerContextBuilder->createFromRequest($request, true, [
                'resource_class' => $resourceClass,
                'resource_operation_name' => 'resource',
            ]);
            $deserializationContext = $this->serializerContextBuilder->createFromRequest($request, false, [
                'resource_class' => $resourceClass,
                'resource_operation_name' => 'resource',
            ]);
        } catch (ResourceClassNotFoundException $exception) {
            return [];
        }
        $serializationGroups = (array) ($serializationContext[AbstractNormalizer::GROUPS] ?? []);
        $deserializationGroups = (array) ($deserializationContext[AbstractNormalizer::GROUPS] ?? []);

        if (!$splitDeserialization) {
            $serializationGroups = array_unique(array_merge($serializationGroups, $deserializationGroups));
            $deserializationGroups = [];
        }

        $options = [];
        if ($serializationGroups) {
            $options['serializer_groups'] = $serializationGroups;
        }
        if ($deserializationGroups) {
            $options['deserializer_groups'] = $deserializationGroups;
        }

        return $options;
    }
}
