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

use ApiPlatform\Serializer\CacheableSupportsMethodInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class ValidationExceptionNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(private readonly NormalizerInterface $decorated, private readonly ?NameConverterInterface $nameConverter)
    {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $messages = [];
        foreach ($object->getConstraintViolationList() as $violation) {
            $class = \is_object($root = $violation->getRoot()) ? $root::class : null;

            if ($this->nameConverter instanceof AdvancedNameConverterInterface) {
                $propertyPath = $this->nameConverter->normalize($violation->getPropertyPath(), $class, $format);
            } elseif ($this->nameConverter instanceof NameConverterInterface) {
                $propertyPath = $this->nameConverter->normalize($violation->getPropertyPath());
            } else {
                $propertyPath = $violation->getPropertyPath();
            }

            $messages[] = ($propertyPath ? "{$propertyPath}: " : '').$violation->getMessage();
        }

        $str = implode("\n", $messages);
        $object->setDetail($str);

        return $this->decorated->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ValidationException && $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        if (method_exists(Serializer::class, 'getSupportedTypes')) {
            trigger_deprecation(
                'api-platform/core',
                '3.1',
                'The "%s()" method is deprecated, use "getSupportedTypes()" instead.',
                __METHOD__
            );
        }

        return false;
    }

    public function getSupportedTypes($format): array
    {
        return [ValidationException::class => false];
    }
}
