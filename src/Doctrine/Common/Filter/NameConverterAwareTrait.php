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

namespace ApiPlatform\Doctrine\Common\Filter;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Holds an optional name converter and (de)normalizes property names through it.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait NameConverterAwareTrait
{
    private ?NameConverterInterface $nameConverter = null;

    public function hasNameConverter(): bool
    {
        return $this->nameConverter instanceof NameConverterInterface;
    }

    public function getNameConverter(): ?NameConverterInterface
    {
        return $this->nameConverter;
    }

    public function setNameConverter(NameConverterInterface $nameConverter): void
    {
        $this->nameConverter = $nameConverter;
    }

    protected function denormalizePropertyName(string|int $property): string
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return (string) $property;
        }

        return implode('.', array_map($this->nameConverter->denormalize(...), explode('.', (string) $property)));
    }

    protected function normalizePropertyName(string $property): string
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map($this->nameConverter->normalize(...), explode('.', $property)));
    }
}
