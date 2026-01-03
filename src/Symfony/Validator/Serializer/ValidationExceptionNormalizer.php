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

namespace ApiPlatform\Symfony\Validator\Serializer;

use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ValidationExceptionNormalizer implements NormalizerInterface
{
    public function __construct(private readonly NormalizerInterface $decorated, private readonly ?NameConverterInterface $nameConverter)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $messages = [];
        foreach ($data->getConstraintViolationList() as $violation) {
            $class = \is_object($root = $violation->getRoot()) ? $root::class : null;

            if ($this->nameConverter) {
                $propertyPath = $this->nameConverter->normalize($violation->getPropertyPath(), $class, $format);
            } else {
                $propertyPath = $violation->getPropertyPath();
            }

            $messages[] = ($propertyPath ? "{$propertyPath}: " : '').$violation->getMessage();
        }

        $str = implode("\n", $messages);
        $data->setDetail($str);

        return $this->decorated->normalize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ValidationException && $this->decorated->supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return [ValidationException::class => false];
    }
}
