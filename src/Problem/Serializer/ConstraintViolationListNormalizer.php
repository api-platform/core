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

namespace ApiPlatform\Core\Problem\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} the API Problem spec (RFC 7807).
 *
 * @see https://tools.ietf.org/html/rfc7807
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonproblem';

    /**
     * @var bool|array
     */
    private $serializePayloadFields;

    public function __construct($serializePayloadFields = false)
    {
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
            'type' => $context['type'] ?? 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => $context['title'] ?? 'An error occurred',
            'detail' => $messages ? implode("\n", $messages) : (string) $object,
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
