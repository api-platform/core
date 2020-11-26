<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;

final class <?= $class_name ?> implements ContextAwareDataPersisterInterface
{
    /**
    * {@inheritdoc}
    */
    public function supports($data, array $context = []): bool
    {
        // return $data instanceof MyEntity;
        return false;
    }

    /**
    * {@inheritdoc}
    */
    public function persist($data, array $context = [])
    {
        // call your persistence layer to save $data
        return $data;
    }

    /**
    * {@inheritdoc}
    */
    public function remove($data, array $context = [])
    {
        // call your persistence layer to delete $data
    }
}
