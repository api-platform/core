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

trait IriContextTrait
{
    public function getIriContextFromOperationContext(array $context, string $resourceClass = null, string $objectClass = null, bool $forceItem = false): array
    {
        // We can't be sure that the context is the correct one, this happens when the Object we want the IRI from is not the same as the requested one
        if ($objectClass && $resourceClass !== $objectClass) {
            return [];
        }

        // This is probably an old version of the context, links are created in the Resource Metadata and are tighten to a given Operation
        if (!isset($context['links'])) {
            return [];
        }

        $link = $context['links'][0];

        if ($forceItem) {
            foreach ($context['links'] as $operationLink) {
                if (false === $operationLink[3]) {
                    $link = $operationLink;
                    break;
                }
            }
        }

        return [
            'operation_name' => $link[0],
            'identifiers' => $link[1], 
            'has_composite_identifier' => $link[2]
        ];
    }
}
