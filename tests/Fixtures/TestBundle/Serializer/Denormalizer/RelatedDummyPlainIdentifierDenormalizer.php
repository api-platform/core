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

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Metadata\Get;
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

    public function __construct(private $iriConverter)
    {
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

            return $this->denormalizer->denormalize($data, $class, $format, $context + [self::class => true]);
        }

        $iriConverterContext = ['uri_variables' => ['id' => $data['thirdLevel']]] + $context;

        $data['thirdLevel'] = $this->iriConverter->getIriFromResource(
            RelatedDummyEntity::class === $class ? ThirdLevelEntity::class : ThirdLevelDocument::class,
            UrlGeneratorInterface::ABS_PATH,
            new Get(),
            $iriConverterContext
        );

        return $this->denormalizer->denormalize($data, $class, $format, $context + [self::class => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return 'json' === $format
            && (is_a($type, RelatedDummyEntity::class, true) || is_a($type, RelatedDummyDocument::class, true))
            && '1' === ($data['thirdLevel'] ?? null)
            && !isset($context[self::class]);
    }
}
