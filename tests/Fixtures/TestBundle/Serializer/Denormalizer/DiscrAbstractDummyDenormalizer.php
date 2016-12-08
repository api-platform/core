<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\Denormalizer;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DiscrAbstractDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DiscrFirstDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DiscrSecondDummy;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DiscrAbstractDummyDenormalizer implements DenormalizerInterface
{
    /** @var DenormalizerInterface */
    protected $denormalizer;

    /**
     * @param DenormalizerInterface $denormalizer
     *
     * @return static
     */
    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;

        return $this;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if ($data['@type'] == 'DiscrFirstDummy') {
            $dataType = DiscrFirstDummy::class;
        } elseif ($data['@type'] == 'DiscrSecondDummy') {
            $dataType = DiscrSecondDummy::class;
        } else {
            throw new \Exception('Not implemented');
        }

        unset($data['@type']);

        $denormalized = $this->denormalizer->denormalize(
            $data,
            $dataType,
            $format,
            [
                'resource_class' => $dataType,
            ] + $context);

        return $denormalized;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return ($type === DiscrAbstractDummy::class)
            && is_array($data)
            && isset($data['@type']);
    }

}