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

namespace ApiPlatform\JsonApi\Serializer;

use ApiPlatform\Problem\Serializer\ErrorNormalizerTrait;
use ApiPlatform\Serializer\CacheableSupportsMethodInterface;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface as LegacyConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Converts {@see \Exception} or {@see FlattenException} or to a JSON API error representation.
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class ErrorNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use ErrorNormalizerTrait;

    public const FORMAT = 'jsonapi';
    public const TITLE = 'title';
    private array $defaultContext = [
        self::TITLE => 'An error occurred',
    ];

    public function __construct(private readonly bool $debug = false, array $defaultContext = [], private ?NormalizerInterface $itemNormalizer = null, private ?NormalizerInterface $constraintViolationListNormalizer = null)
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        // TODO: in api platform 4 this will be the default, note that JSON:API is close to Problem so we should use the same normalizer
        if ($context['rfc_7807_compliant_errors'] ?? false) {
            if ($object instanceof LegacyConstraintViolationListAwareExceptionInterface || $object instanceof ConstraintViolationListAwareExceptionInterface) {
                // TODO: return ['errors' => $this->constraintViolationListNormalizer(...)]
                return $this->constraintViolationListNormalizer->normalize($object->getConstraintViolationList(), $format, $context);
            }

            $jsonApiObject = $this->itemNormalizer->normalize($object, $format, $context);
            $error = $jsonApiObject['data']['attributes'];
            $error['id'] = $jsonApiObject['data']['id'];
            $error['type'] = $jsonApiObject['data']['id'];

            return ['errors' => [$error]];
        }

        $data = [
            'title' => $context[self::TITLE] ?? $this->defaultContext[self::TITLE],
            'description' => $this->getErrorMessage($object, $context, $this->debug),
        ];

        if (null !== $errorCode = $this->getErrorCode($object)) {
            $data['code'] = $errorCode;
        }

        if ($this->debug && null !== $trace = $object->getTrace()) {
            $data['trace'] = $trace;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException);
    }

    public function getSupportedTypes($format): array
    {
        if (self::FORMAT === $format) {
            return [
                \Exception::class => true,
                FlattenException::class => true,
            ];
        }

        return [];
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

        return true;
    }
}
