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

namespace ApiPlatform\Laravel\Eloquent\Serializer\Mapping\Loader;

use Symfony\Component\Serializer\Exception\MappingException;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader as SymfonyAttributeLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

final class AttributeLoader implements LoaderInterface
{
    public function __construct(private readonly SymfonyAttributeLoader $decorated)
    {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        try {
            return $this->decorated->loadClassMetadata($classMetadata);
        } catch (MappingException) {
            // We ignore this exception as we allow groups on methods not starting with get/is/has/set
            return false;
        }
    }
}
