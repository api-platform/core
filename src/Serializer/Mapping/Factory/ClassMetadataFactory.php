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

namespace ApiPlatform\Serializer\Mapping\Factory;

use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

final class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    use ClassInfoTrait;

    public function __construct(private readonly ClassMetadataFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($value): ClassMetadataInterface
    {
        return $this->decorated->getMetadataFor(\is_object($value) ? $this->getObjectClass($value) : $this->getRealClassName($value));
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataFor(mixed $value): bool
    {
        return $this->decorated->hasMetadataFor(\is_object($value) ? $this->getObjectClass($value) : $this->getRealClassName($value));
    }
}
