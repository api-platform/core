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

namespace ApiPlatform\Core\Bridge\Eloquent\Serializer\Mapping\Loader;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * Decorate the Symfony annotation loader to avoid loading an Eloquent model and its interfaces.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class AnnotationLoader implements LoaderInterface
{
    private $decorated;

    public function __construct(LoaderInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (is_a($classMetadata->getName(), Model::class, true)) {
            return false;
        }

        $modelReflectionClass = new \ReflectionClass(Model::class);

        if (\in_array($classMetadata->getName(), $modelReflectionClass->getInterfaceNames(), true)) {
            return false;
        }

        return $this->decorated->loadClassMetadata($classMetadata);
    }
}
