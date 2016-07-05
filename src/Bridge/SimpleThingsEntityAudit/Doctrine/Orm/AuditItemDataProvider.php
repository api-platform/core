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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\ItemDataProvider;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\AuditReader;

/**
 * Item data provider for the Doctrine ORM with the entity Audit Extension.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class AuditItemDataProvider extends ItemDataProvider
{
    private $auditManager;
    private $auditReader;

    /**
     * @param ManagerRegistry                        $managerRegistry
     * @param PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory
     * @param PropertyMetadataFactoryInterface       $propertyMetadataFactory
     * @param QueryItemExtensionInterface[]          $itemExtensions
     * @param AuditReader                            $auditReader
     * @param AuditManager                           $auditManager
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, array $itemExtensions, AuditReader $auditReader, AuditManager $auditManager)
    {
        parent::__construct($managerRegistry, $propertyNameCollectionFactory, $propertyMetadataFactory, $itemExtensions);
        $this->auditReader = $auditReader;
        $this->auditManager = $auditManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false)
    {
        $resourceClass = str_replace('Audit', '', $resourceClass);
        if (true === $this->auditManager->getMetadataFactory()->isAudited($resourceClass)
        ) {
            return $this->auditReader->find($resourceClass, $id, 100 /*configurable ?*/);
        }

        parent::getItem($resourceClass, $id, $operationName, $fetchData);
    }
}
