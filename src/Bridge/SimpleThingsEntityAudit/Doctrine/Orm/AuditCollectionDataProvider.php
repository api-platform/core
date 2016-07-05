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

use ApiPlatform\Core\Bridge\Doctrine\Orm\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\AuditReader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Collection data provider for the Doctrine ORM with the EntityAudit extension.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class AuditCollectionDataProvider extends CollectionDataProvider
{
    private $auditManager;
    private $auditReader;

    /**
     * @param ManagerRegistry                     $managerRegistry
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     * @param AuditReader                         $auditReader
     * @param AuditManager                        $auditManager
     */
    public function __construct(ManagerRegistry $managerRegistry, array $collectionExtensions, AuditReader $auditReader, AuditManager $auditManager)
    {
        parent::__construct($managerRegistry, $collectionExtensions);
        $this->auditManager = $auditManager;
        $this->auditReader = $auditReader;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null)
    {
        $resourceClass = str_replace('Audit', '', $resourceClass);
        if (true === $this->auditManager->getMetadataFactory()->isAudited($resourceClass)
        ) {
            throw new NotFoundHttpException(sprintf('Please add an {id} to get the revisions of %s', $resourceClass));
        }

        return parent::getCollection($resourceClass, $operationName);
    }
}
