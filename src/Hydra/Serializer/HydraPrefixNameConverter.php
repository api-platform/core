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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\JsonLd\ContextBuilder;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final readonly class HydraPrefixNameConverter implements NameConverterInterface, AdvancedNameConverterInterface
{
    /**
     * @param array<string,mixed> $defaultContext
     */
    public function __construct(private NameConverterInterface $nameConverter, private array $defaultContext = [])
    {
    }

    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        $context += $this->defaultContext;
        $name = $this->nameConverter->normalize($propertyName, $class, $format, $context);

        if (true === ($context[ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX] ?? true)) {
            return $name;
        }

        return str_starts_with($name, ContextBuilder::HYDRA_PREFIX) ? str_replace(ContextBuilder::HYDRA_PREFIX, '', $name) : $name;
    }

    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        return $this->nameConverter->denormalize($propertyName, $class, $format, $context);
    }
}
