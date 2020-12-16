<?php


declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Serializer\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class SerializerIgnoredPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    /**
     * @var PropertyNameCollectionFactoryInterface
     */
    private $decorated;
    
    /**
     * @var PropertyNameCollectionFactoryInterface
     */
    private $serializerClassMetadataFactory;

    public function __construct(ClassMetadataFactoryInterface $serializerClassMetadataFactory, PropertyNameCollectionFactoryInterface $decorated)
    {
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
        $this->decorated = $decorated;
    }
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNameCollection = $this->decorated->create($resourceClass, $options);
        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($resourceClass);
        $propertyNames = [];
        foreach($propertyNameCollection->getIterator() as $propertyName){
            if(
                !isset($serializerClassMetadata->getAttributesMetadata()[$propertyName]) ||
                !$serializerClassMetadata->getAttributesMetadata()[$propertyName]->isIgnored()
            ){
                $propertyNames[$propertyName] = $propertyName;
            }
        }
        return new PropertyNameCollection(array_values($propertyNames));
    }
}
