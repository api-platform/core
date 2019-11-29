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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\EventListener;

use ApiPlatform\Core\Bridge\Doctrine\Common\EventListener\AbstractPublishMercureUpdatesListener;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;

/**
 * Publishes resources updates to the Mercure hub.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
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

        $uow = $eventArgs->getDocumentManager()->getUnitOfWork();

        foreach ($uow->getScheduledDocumentInsertions() as $document) {
            $this->storeObjectToPublish($document, 'createdObjects');
        }

        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            $this->storeObjectToPublish($document, 'updatedObjects');
        }

        foreach ($uow->getScheduledDocumentDeletions() as $document) {
            $this->storeObjectToPublish($document, 'deletedObjects');
        }
    }
}
