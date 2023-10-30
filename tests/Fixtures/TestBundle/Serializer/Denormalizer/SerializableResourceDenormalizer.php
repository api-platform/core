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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Serializer\Denormalizer;

use ApiPlatform\Tests\Fixtures\TestBundle\Model\SerializableResource;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SerializableResourceDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $resource = new $class();
        $resource->bar = $data['bar'];
        $resource->foo = $data['foo'];
        $resource->id = $data['id'];

        return $resource;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return 'json' === $format && SerializableResource::class === $type && \is_array($data);
    }
}
