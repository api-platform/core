<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Symfony\Validator\Hydra\Serializer;

use Dunglas\ApiBundle\Api\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} to a Hydra error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer implements NormalizerInterface
{
    const FORMAT = 'hydra-error';

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $violations = [];
        $message = '';

        foreach ($object as $violation) {
            $violations[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];

            $propertyPath = $violation->getPropertyPath();
            $prefix = $propertyPath ? sprintf('%s: ', $propertyPath) : '';

            $message .= $prefix.$violation->getMessage()."\n";
        }

        return [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList']),
            '@type' => 'ConstraintViolationList',
            'hydra:title' => $context['title'] ?? 'An error occurred',
            'hydra:description' => $message ?? (string) $object,
            'violations' => $violations,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof ConstraintViolationListInterface;
    }
}
