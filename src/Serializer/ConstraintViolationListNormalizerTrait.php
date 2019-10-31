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

namespace ApiPlatform\Core\Serializer;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Common features regarding Constraint Violation normalization.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait ConstraintViolationListNormalizerTrait
{
    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    protected function getMessagesAndViolations(ConstraintViolationListInterface $constraintViolationList): array
    {
        @trigger_error('Using Api-platform\'s ConstraintViolationList Normalizer is deprecated since 2.6 and will not be possible in 3.0. Please set "api_platform.enable_symfony_violation_normalizer" parameter to "true".', E_USER_DEPRECATED);

        $violations = $messages = [];

        foreach ($constraintViolationList as $violation) {
            $class = \is_object($root = $violation->getRoot()) ? \get_class($root) : null;
            $violationData = [
                'propertyPath' => $this->nameConverter ? $this->nameConverter->normalize($violation->getPropertyPath(), $class, static::FORMAT) : $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];

            $constraint = $violation->getConstraint();
            if ($this->serializePayloadFields && $constraint && $constraint->payload) {
                // If some fields are whitelisted, only them are added
                $payloadFields = null === $this->serializePayloadFields ? $constraint->payload : array_intersect_key($constraint->payload, array_flip($this->serializePayloadFields));
                $payloadFields && $violationData['payload'] = $payloadFields;
            }

            $violations[] = $violationData;
            $messages[] = ($violationData['propertyPath'] ? "{$violationData['propertyPath']}: " : '').$violationData['message'];
        }

        return [$messages, $violations];
    }

    protected function addPayloadToViolations(ConstraintViolationListInterface $object, array &$violations): void
    {
        foreach ($object as $key => $violation) {
            $constraint = $violation->getConstraint();
            if ($this->serializePayloadFields && $constraint && $constraint->payload) {
                // If some fields are whitelisted, only them are added
                $payloadFields = null === $this->serializePayloadFields ? $constraint->payload : array_intersect_key($constraint->payload, array_flip($this->serializePayloadFields));
                $payloadFields && $violations[$key]['payload'] = $payloadFields;
            }
        }
    }
}
