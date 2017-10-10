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
        if (isset($data['id']) && !isset($context[self::OBJECT_TO_POPULATE])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new InvalidArgumentException('Update is not allowed for this operation.');
            }

            $this->updateObjectToPopulate($data, $context);
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    private function updateObjectToPopulate(array $data, array &$context)
    {
        try {
            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri((string) $data['id'], $context + ['fetch_data' => true]);
        } catch (InvalidArgumentException $e) {
            $identifier = null;
            foreach ($this->propertyNameCollectionFactory->create($context['resource_class'], $context) as $propertyName) {
                if (true === $this->propertyMetadataFactory->create($context['resource_class'], $propertyName)->isIdentifier()) {
                    $identifier = $propertyName;
                    break;
                }
            }

            if (null === $identifier) {
                throw $e;
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri(sprintf('%s/%s', $this->iriConverter->getIriFromResourceClass($context['resource_class']), $data[$identifier]), $context + ['fetch_data' => true]);
        }
    }
}
