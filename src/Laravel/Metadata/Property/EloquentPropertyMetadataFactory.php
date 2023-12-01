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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\PropertyInfo\Type;

/**
 * Use Doctrine metadata to populate the identifier property.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EloquentPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    // TODO: copy paste ShowModelCommand to a service lazy right now
    private $modelFactory;

    public function __construct(private Application $application, private readonly PropertyMetadataFactoryInterface $decorated)
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

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        try {
            $refl = new \ReflectionClass($resourceClass);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $this->decorated->create($resourceClass, $property, $options);
        }

        if (!$model instanceof Model) {
            return $this->decorated->create($resourceClass, $property, $options);
        }

        try {
            $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
        } catch (PropertyNotFoundException) {
            $propertyMetadata = new ApiProperty();
        }

        if ($model->getKeyName() === $property) {
            $propertyMetadata = $propertyMetadata->withIdentifier(true);
        }

        // I'm building a prototype this is ugly we need to put this in a service and work around fillable/hidden also
        foreach ($this->modelFactory->getAttributes($model) as $p) {
            if ($p['name'] !== $property) {
                continue;
            }

            $builtinType = $p['cast'] ?? $p['type'];
            if ('datetime' === $builtinType) {
                $type = new Type(Type::BUILTIN_TYPE_OBJECT, $p['nullable'], \DateTimeImmutable::class);
            } else {
                $type = new Type($builtinType, $p['nullable']);
            }

            $propertyMetadata = $propertyMetadata->withBuiltinTypes([$type])->withWritable(true)->withReadable(!($p['hidden'] ?? false));
        }

        return $propertyMetadata;
    }
}
