<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra\Serializer;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} to a Hydra error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ConstraintViolationListNormalizer implements NormalizerInterface
{
    const FORMAT = 'hydra-error';

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $violations = [];
        if ($object instanceof ConstraintViolationListInterface) {
            $message = '';

            foreach ($object as $violation) {
                $violations[] = [
                    'propertyPath' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];

                $message .= ($violation->getPropertyPath() ? $violation->getPropertyPath().': ' : '').$violation->getMessage()."\n";
            }
        }

        return [
            '@context' => $this->router->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList']),
            '@type' => 'ConstraintViolationList',
            'hydra:title' => isset($context['title']) ? $context['title'] : 'An error occurred',
            'hydra:description' => isset($message) ? $message : (string) $object,
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
