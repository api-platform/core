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

use ApiPlatform\Serializer\AbstractConstraintViolationListNormalizer;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts {@see \Symfony\Component\Validator\ConstraintViolationListInterface} the API Problem spec (RFC 7807).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ConstraintViolationListNormalizer extends AbstractConstraintViolationListNormalizer
{
    public const FORMAT = 'json';

    private array $defaultContext = [];

    public function __construct(array $serializePayloadFields = null, NameConverterInterface $nameConverter = null, array $defaultContext = [])
    {
        parent::__construct($serializePayloadFields, $nameConverter);

        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return $this->getViolations($object);
    }
}
