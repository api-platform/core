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

namespace ApiPlatform\Problem\Serializer;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes errors according to the API Problem spec (RFC 7807).
 *
 * @see https://tools.ietf.org/html/rfc7807
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ErrorNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    use ErrorNormalizerTrait;
    public const FORMAT = 'jsonproblem';
    public const TYPE = 'type';
    public const TITLE = 'title';
    private array $defaultContext = [
        self::TYPE => 'https://tools.ietf.org/html/rfc2616#section-10',
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
        $data = [
            'type' => $context[self::TYPE] ?? $this->defaultContext[self::TYPE],
            'title' => $context[self::TITLE] ?? $this->defaultContext[self::TITLE],
            'detail' => $this->getErrorMessage($object, $context, $this->debug),
        ];

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
        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
