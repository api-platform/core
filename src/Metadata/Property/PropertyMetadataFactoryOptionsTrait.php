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

namespace ApiPlatform\Core\Metadata\Property;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

trait PropertyMetadataFactoryOptionsTrait
{
    /**
     * Gets a valid context for property metadata factories.
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php
     */
    private function getPropertyMetadataFactoryOptions(array $context): array
    {
        $options = [];

        if (isset($context[AbstractNormalizer::GROUPS])) {
            $options['serializer_groups'] = $context[AbstractNormalizer::GROUPS];
        }

        if (isset($context['collection_operation_name'])) {
            $options['collection_operation_name'] = $context['collection_operation_name'];
        } elseif (isset($context['item_operation_name'])) {
            $options['item_operation_name'] = $context['item_operation_name'];
        }

        return $options;
    }
}
