<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\SimpleThingsEntityAudit\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use SimpleThings\EntityAudit\AuditManager;

/**
 * Creates a resource name collection from EntityAudit Resources.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class EntityAuditResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $resourceNameFactory;
    private $auditManager;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameFactory = null, AuditManager $auditManager)
    {
        $this->resourceNameFactory = $resourceNameFactory;
        $this->auditManager = $auditManager;
    }

    /**
     * {@inheritdoc}
     */
    public function create() : ResourceNameCollection
    {
        $ressourceClasses = [];

        foreach ($this->resourceNameFactory->create() as $resourceClass) {
            if ($this->auditManager->getMetadataFactory()->isAudited($resourceClass)) {
                $ressourceClasses[$resourceClass] = true;
            } else {
                $ressourceClasses[$resourceClass] = true;
            }
        }

        return new ResourceNameCollection(array_keys($ressourceClasses));
    }
}
