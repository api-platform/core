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

namespace ApiPlatform\Core\GraphQl\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

/**
 * Transforms the passed GraphQL fields to the list of attributes to serialize.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait FieldsToAttributesTrait
{
    /**
     * Retrieves fields, recursively replaces the "_id" key (the raw id) by "id" (the name of the property expected by the Serializer) and flattens edge and node structures (pagination).
     */
    private function fieldsToAttributes(ResolveInfo $info): array
    {
        $fields = $info->getFieldSelection(PHP_INT_MAX);

        return $this->replaceIdKeys($fields['edges']['node'] ?? $fields);
    }

    private function replaceIdKeys(array $fields): array
    {
        foreach ($fields as $key => $value) {
            if ('_id' === $key) {
                $fields['id'] = $fields['_id'];
                unset($fields['_id']);
            } elseif (\is_array($fields[$key])) {
                $fields[$key] = $this->replaceIdKeys($fields[$key]);
            }
        }

        return $fields;
    }
}
