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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\EventListener;

use ApiPlatform\Core\Bridge\Doctrine\Common\EventListener\AbstractPublishMercureUpdatesListener;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Publishes resources updates to the Mercure hub.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class PublishMercureUpdatesListener extends AbstractPublishMercureUpdatesListener
{
    /**
     * {@inheritdoc}
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if (!$eventArgs instanceof OnFlushEventArgs) {
            return;
        }

        $uow = $eventArgs->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->storeObjectToPublish($entity, 'createdObjects');
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->storeObjectToPublish($entity, 'updatedObjects');
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->storeObjectToPublish($entity, 'deletedObjects');
        }
    }
}
