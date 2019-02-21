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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class OverrideDocumentationNormalizer implements NormalizerInterface
{
    private $documentationNormalizer;

    public function __construct(NormalizerInterface $documentationNormalizer)
    {
        $this->documentationNormalizer = $documentationNormalizer;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->documentationNormalizer->normalize($object, $format, $context);
        if (isset($data['definitions'])) {
            $data['definitions']['RamseyUuidDummy']['properties']['id']['description'] = 'The dummy id';
        } else {
            $data['components']['schemas']['RamseyUuidDummy']['properties']['id']['description'] = 'The dummy id';
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->documentationNormalizer->supportsNormalization($data, $format);
    }
}
