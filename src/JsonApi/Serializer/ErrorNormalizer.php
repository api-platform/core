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

    public function __construct(private readonly bool $debug = false, array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        trigger_deprecation('api-platform', '3.2', sprintf('The class "%s" is deprecated in favor of using an Error resource.', __CLASS__));
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
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        if ($context['skip_deprecated_exception_normalizers'] ?? false) {
            return false;
        }

        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException);
    }

    public function getSupportedTypes($format): array
    {
        if (self::FORMAT === $format) {
            return [
                \Exception::class => false,
                FlattenException::class => false,
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

        return false;
    }
}
