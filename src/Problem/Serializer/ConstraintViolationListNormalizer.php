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

use ApiPlatform\Core\Serializer\ConstraintViolationListNormalizerTrait;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ConstraintViolationListNormalizer as BaseConstraintViolationListNormalizer;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} the API Problem spec (RFC 7807).
 *
 * @see https://tools.ietf.org/html/rfc7807
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer extends BaseConstraintViolationListNormalizer
{
    use ConstraintViolationListNormalizerTrait;

    public const FORMAT = 'jsonproblem';
    public const TYPE = 'type';
    public const TITLE = 'title';

    private $defaultContext = [
        self::TYPE => 'https://tools.ietf.org/html/rfc2616#section-10',
        self::TITLE => 'An error occurred',
    ];

    private $serializePayloadFields;

    private $nameConverter;

    private $useSymfonyNormalizer;

    public function __construct(array $serializePayloadFields = null, NameConverterInterface $nameConverter = null, array $defaultContext = [], bool $useSymfonyNormalizer = false)
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
        $this->nameConverter = $nameConverter;

        parent::__construct($this->defaultContext, $this->nameConverter);

        $this->serializePayloadFields = $serializePayloadFields;
        $this->useSymfonyNormalizer = $useSymfonyNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return static::FORMAT === $format && $data instanceof ConstraintViolationListInterface && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($this->useSymfonyNormalizer) {
            ['type' => $type, 'title' => $title, 'detail' => $detail, 'violations' => $violations] = parent::normalize($object, $format, $context);
            $this->addPayloadToViolations($object, $violations);

            return ['type' => $type, 'title' => $title, 'detail' => $detail, 'violations' => $violations];
        }

        [$messages, $violations] = $this->getMessagesAndViolations($object);

        return [
            'type' => $context[self::TYPE] ?? $this->defaultContext[self::TYPE],
            'title' => $context[self::TITLE] ?? $this->defaultContext[self::TITLE],
            'detail' => $messages ? implode("\n", $messages) : (string) $object,
            'violations' => $violations,
        ];
    }
}
