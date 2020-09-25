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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\MessengerWithArray as MessengerWithArrayDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\MessengerInput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\MessengerWithArray as MessengerWithArrayEntity;

final class MessengerWithArrayDtoDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        /** @var MessengerInput */
        $data = $object;

        $resourceObject = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] ?? new $context['resource_class']();
        $resourceObject->name = $data->var;

        return $resourceObject;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($object, string $to, array $context = []): bool
    {
        return \in_array($to, [MessengerWithArrayEntity::class, MessengerWithArrayDocument::class], true) && null !== ($context['input']['class'] ?? null);
    }
}
