<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;

final class <?= $class_name ?> implements ContextAwareCollectionDataProviderInterface, ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
    * {@inheritdoc}
    */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        // return MyEntity::class === $resourceClass;
        return false;
    }

    /**
    * {@inheritdoc}
    */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        // Retrieve the collection from somewhere
    }

    /**
    * {@inheritdoc}
    */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        // Retrieve the item from somewhere then return it or null if not found
    }
}
