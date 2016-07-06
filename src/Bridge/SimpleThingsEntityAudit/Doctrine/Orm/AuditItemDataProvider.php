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

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\AuditReader;

/**
 * Item data provider for the Doctrine ORM with the entity Audit Extension.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class AuditItemDataProvider implements ItemDataProviderInterface
{
    private $auditManager;
    private $auditReader;

    /**
     * @param AuditReader  $auditReader
     * @param AuditManager $auditManager
     */
    public function __construct(AuditReader $auditReader, AuditManager $auditManager)
    {
        $this->auditReader = $auditReader;
        $this->auditManager = $auditManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false)
    {
        if ('audits' === $operationName && true === $this->auditManager->getMetadataFactory()->isAudited($resourceClass)
        ) {
            return $this->auditReader->find($resourceClass, $id, 100 /*configurable ?*/);
        }
        throw new ResourceClassNotSupportedException(sprintf('Resource class %s is not Audited', $resourceClass));
    }
}
