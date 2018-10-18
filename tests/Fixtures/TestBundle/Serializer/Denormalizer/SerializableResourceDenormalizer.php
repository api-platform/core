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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\Denormalizer;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\SerializableResource as SerializableResourceDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\SerializableResource;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SerializableResourceDenormalizer implements DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $resource = new $class();
        $resource->bar = $data['bar'];
        $resource->foo = $data['foo'];
        $resource->id = $data['id'];

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return 'json' === $format && \in_array($type, [SerializableResource::class, SerializableResourceDocument::class], true) && \is_array($data);
    }
}
