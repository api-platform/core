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

namespace ApiPlatform\Laravel\Eloquent\PropertyInfo;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;

class EloquentExtractor implements PropertyAccessExtractorInterface
{
    public function __construct(private readonly ModelMetadata $modelMetadata)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function isReadable(string $class, string $property, array $context = []): ?bool
    {
        if (!is_a($class, Model::class, true)) {
            return null;
        }

        try {
            $refl = new \ReflectionClass($class);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return null;
        }

        foreach ($this->modelMetadata->getAttributes($model) as $p) {
            if ($p['name'] !== $property) {
                continue;
            }

            if (($visible = $model->getVisible()) && \in_array($property, $visible, true)) {
                return true;
            }

            if (($hidden = $model->getHidden()) && \in_array($property, $hidden, true)) {
                return false;
            }

            return true;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function isWritable(string $class, string $property, array $context = []): ?bool
    {
        if (!is_a($class, Model::class, true)) {
            return null;
        }

        try {
            $refl = new \ReflectionClass($class);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return null;
        }

        foreach ($this->modelMetadata->getAttributes($model) as $p) {
            if ($p['name'] !== $property) {
                continue;
            }

            if ($fillable = $model->getFillable()) {
                return \in_array($property, $fillable, true);
            }

            return true;
        }

        return null;
    }
}
