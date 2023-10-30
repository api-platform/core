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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy as DummyEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy as RelatedDummyEntity;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

/**
 * BC to keep allow_plain_identifiers working in 2.7 tests
 * TODO: to remove in 3.0.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class DummyPlainIdentifierDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
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

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if ($this->iriConverter instanceof LegacyIriConverterInterface) {
            if (!empty($data['relatedDummy'])) {
                $data['relatedDummy'] = '/related_dummies/'.$data['relatedDummy'];
            }

            if (!empty($data['relatedDummies'])) {
                foreach ($data['relatedDummies'] as $k => $v) {
                    $data['relatedDummies'][$k] = '/related_dummies/'.$v;
                }
            }

            return $this->denormalizer->denormalize($data, $class, $format, $context + [__CLASS__ => true]);
        }

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

        return $this->denormalizer->denormalize($data, $class, $format, $context + [__CLASS__ => true]);
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return 'json' === $format
            && (is_a($type, DummyEntity::class, true) || is_a($type, DummyDocument::class, true))
            && ('1' === ($data['relatedDummy'] ?? null) || ['1'] === ($data['relatedDummies'] ?? null))
            && !isset($context[__CLASS__]);
    }
}
