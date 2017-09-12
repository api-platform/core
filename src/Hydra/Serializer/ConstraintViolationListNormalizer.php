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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} to a Hydra error representation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonld';

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var bool|array
     */
    private $serializePayloadFields;

    public function __construct(UrlGeneratorInterface $urlGenerator, $serializePayloadFields = false)
    {
        $this->urlGenerator = $urlGenerator;
        $this->serializePayloadFields = $serializePayloadFields;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $violations = [];
        $messages = [];

        foreach ($object as $violation) {
            $violationData = [
                'propertyPath' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
            $constraint = $violation->getConstraint();
            if ($this->serializePayloadFields && $constraint && $constraint->payload) {
                if (true === $this->serializePayloadFields) {
                    $violationData['payload'] = $constraint->payload;
                } elseif (is_array($this->serializePayloadFields)) {
                    // We add only fields defined in the config
                    $payloadFields = array_intersect_key($constraint->payload, array_flip($this->serializePayloadFields));
                    if (!empty($payloadFields)) {    // prevent the case where in the config there are fields which are not in the payload
                        $violationData['payload'] = $payloadFields;
                    }
                }
            }
            $violations[] = $violationData;

            $propertyPath = $violation->getPropertyPath();
            $prefix = $propertyPath ? sprintf('%s: ', $propertyPath) : '';

            $messages[] = $prefix.$violation->getMessage();
        }

        return [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList']),
            '@type' => 'ConstraintViolationList',
            'hydra:title' => $context['title'] ?? 'An error occurred',
            'hydra:description' => $messages ? implode("\n", $messages) : (string) $object,
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
