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

use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Generic item normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['id']) && !isset($context['object_to_populate'])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new InvalidArgumentException('Update is not allowed for this operation.');
            }

            $context['object_to_populate'] = $this->iriConverter->getItemFromIri($data['id'], $context + ['fetch_data' => false]);
        }

        return parent::denormalize($data, $class, $format, $context);
    }
}
