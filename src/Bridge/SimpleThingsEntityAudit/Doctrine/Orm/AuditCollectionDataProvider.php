<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\SimpleThingsEntityAudit\Doctrine\Orm;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\AuditReader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Collection data provider for the Doctrine ORM with the EntityAudit extension.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class AuditCollectionDataProvider implements CollectionDataProviderInterface
{
    private $auditManager;
    private $auditReader;

    /**
     * @param AuditReader  $auditReader
     * @param AuditManager $auditManager
     */
    public function __construct(AuditReader $auditReader, AuditManager $auditManager)
    {
        $this->auditManager = $auditManager;
        $this->auditReader = $auditReader;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null)
    {
        if ('audits' === $operationName && true === $this->auditManager->getMetadataFactory()->isAudited($resourceClass)) {
            throw new NotFoundHttpException(sprintf('Please add an {id} to get the revisions of %s', $resourceClass));
            // or delete this ? and use the itemDataProvider as an collection providers too since we need an id to get the revision
        }
        throw new ResourceClassNotSupportedException(sprintf('Resource class %s is not Audited', $resourceClass));
    }
}
