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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy as DummyEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy as RelatedDummyEntity;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * BC to keep allow_plain_identifiers working in 2.7 tests
 * We keep this class as an example on how to work aroung plain identifiers.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class DummyPlainIdentifierDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = []): mixed
    {
        $relatedDummyClass = DummyEntity::class === $class ? RelatedDummyEntity::class : RelatedDummyDocument::class;
        if (!empty($data['relatedDummy'])) {
            $data['relatedDummy'] = $this->iriConverter->getIriFromResource($relatedDummyClass, UrlGeneratorInterface::ABS_PATH, new Get(), ['uri_variables' => [
                'id' => $data['relatedDummy'],
            ]] + $context);
        }

        if (!empty($data['relatedDummies'])) {
            foreach ($data['relatedDummies'] as $k => $v) {
                $data['relatedDummies'][$k] = $this->iriConverter->getIriFromResource($relatedDummyClass, UrlGeneratorInterface::ABS_PATH, new Get(), ['uri_variables' => [
                    'id' => $v,
                ]] + $context);
            }
        }

        return $this->denormalizer->denormalize($data, $class, $format, $context + [self::class => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return 'json' === $format
            && (is_a($type, DummyEntity::class, true) || is_a($type, DummyDocument::class, true))
            && ('1' === ($data['relatedDummy'] ?? null) || ['1'] === ($data['relatedDummies'] ?? null))
            && !isset($context[self::class]);
    }

    public function getSupportedTypes($format): array
    {
        return 'json' === $format ? ['*' => false] : [];
    }
}
