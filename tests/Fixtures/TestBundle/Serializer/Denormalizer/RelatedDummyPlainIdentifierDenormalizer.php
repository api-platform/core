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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel as ThirdLevelDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy as RelatedDummyEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel as ThirdLevelEntity;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

/**
 * BC to keep allow_plain_identifiers working in 2.7 tests
 * TODO: to remove in 3.0.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class RelatedDummyPlainIdentifierDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    /**
     * @var IriConverterInterface|LegacyIriConverterInterface
     */
    private $iriConverter;

    public function __construct($iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if ($this->iriConverter instanceof LegacyIriConverterInterface) {
            $data['thirdLevel'] = '/third_levels/'.$data['thirdLevel'];

            return $this->denormalizer->denormalize($data, $class, $format, $context + [__CLASS__ => true]);
        }

        $iriConverterContext = ['identifiers_values' => ['id' => $data['thirdLevel']], 'force_collection' => false, 'operation' => null] + $context;

        $data['thirdLevel'] = $this->iriConverter->getIriFromResourceClass(
            RelatedDummyEntity::class === $class ? ThirdLevelEntity::class : ThirdLevelDocument::class,
            null,
            UrlGeneratorInterface::ABS_PATH,
            $iriConverterContext
        );

        return $this->denormalizer->denormalize($data, $class, $format, $context + [__CLASS__ => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return 'json' === $format
            && (is_a($type, RelatedDummyEntity::class, true) || is_a($type, RelatedDummyDocument::class, true))
            && '1' === ($data['thirdLevel'] ?? null)
            && !isset($context[__CLASS__]);
    }
}
