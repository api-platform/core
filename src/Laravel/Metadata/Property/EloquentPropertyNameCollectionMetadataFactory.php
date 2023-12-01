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

namespace ApiPlatform\Laravel\Metadata\Property;

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Database\Eloquent\Model;

final class EloquentPropertyNameCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    // TODO: copy paste ShowModelCommand to a service lazy right now
    private $modelFactory;

    public function __construct(private Application $application, private PropertyNameCollectionFactoryInterface $decorated)
    {
        $this->modelFactory = new class() extends ShowModelCommand {
            public function __construct()
            {
            }

            public function getAttributes(...$args)
            {
                return parent::getAttributes(...$args);
            }
        };
        $this->modelFactory->setLaravel($this->application);
    }

    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        try {
            $refl = new \ReflectionClass($resourceClass);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $this->decorated->create($resourceClass, $options);
        }

        if (!$model instanceof Model) {
            return $this->decorated->create($resourceClass, $options);
        }

        $properties = [];
        // When it's an Eloquent model we read attributes from database (@see ShowModelCommand)
        foreach ($this->modelFactory->getAttributes($model) as $property) {
            if ($property['hidden'] ?? false) {
                continue;
            }

            $properties[] = $property['name'];
        }

        return new PropertyNameCollection($properties);
    }
}
