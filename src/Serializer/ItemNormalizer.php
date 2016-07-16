<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Serializer;

/**
 * Generic item normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $rawData = parent::normalize($object, $format, $context);
        if (!is_array($rawData)) {
            return $rawData;
        }

        if (!isset($data['id'])) {
            $data['id'] = $this->iriConverter->getIriFromItem($object);
        }

        return array_merge($data, $rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['id']) && !isset($context['object_to_populate'])) {
            $context['object_to_populate'] = $this->iriConverter->getItemFromIri($data['id'], true);
        }

        return parent::denormalize($data, $class, $format, $context);
    }
}
