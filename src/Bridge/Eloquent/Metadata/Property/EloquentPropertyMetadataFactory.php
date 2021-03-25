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

namespace ApiPlatform\Core\Bridge\Eloquent\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Illuminate\Database\Eloquent\Model;

/**
 * Use Eloquent to populate the identifier property.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EloquentPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $decorated;

    public function __construct(PropertyMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        if (!is_subclass_of($resourceClass, Model::class, true)) {
            return $propertyMetadata;
        }

        if (null !== $propertyMetadata->isIdentifier()) {
            return $propertyMetadata;
        }

        /** @var Model $model */
        $model = new $resourceClass();

        $identifier = $model->getKeyName();

        if ($identifier === $property) {
            $propertyMetadata = $propertyMetadata->withIdentifier(true);

            if (null === $propertyMetadata->isWritable()) {
                $propertyMetadata = $propertyMetadata->withWritable(false);
            }
        }

        if (null === $propertyMetadata->isIdentifier()) {
            $propertyMetadata = $propertyMetadata->withIdentifier(false);
        }

        return $propertyMetadata;
    }
}
