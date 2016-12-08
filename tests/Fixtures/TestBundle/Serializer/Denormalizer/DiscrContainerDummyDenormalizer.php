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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DiscrContainerDummy;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DiscrContainerDummyDenormalizer implements DenormalizerInterface
{
    /**
     * Ugly way to have a context in the supportsDenormalization method
     *
     * @var bool
     */
    static protected $youShallNotPass = false;

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
        static::$youShallNotPass = true;
        /** @var DiscrContainerDummy $denormalized */
        $denormalized = $this->denormalizer->denormalize($data, $class, $format, $context);
        static::$youShallNotPass = false;

        if ($denormalized instanceof DiscrContainerDummy) {
            $this->populate($denormalized);
        }

        return $denormalized;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return ($type === DiscrContainerDummy::class) && !static::$youShallNotPass;
    }

    /**
     * @param DiscrContainerDummy $dummy
     */
    private function populate(DiscrContainerDummy $dummy)
    {
        foreach ($dummy->getCollection() as $elm) {
            $elm->setParent($dummy);
        }
    }

}