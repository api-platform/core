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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use SimpleThings\EntityAudit\AuditManager;

/**
 * Creates a resource metadata from EntityAudit Entity.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class EntityAuditResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $resourceMetadataFactory;
    private $auditManager;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory = null, AuditManager $auditManager)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->auditManager = $auditManager;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass) : ResourceMetadata
    {
        $auditClassName = str_replace('Audit', '', $resourceClass);
        $resourceClassMetadata = $this->resourceMetadataFactory->create($auditClassName);
        if (true === $this->auditManager->getMetadataFactory()->isAudited($auditClassName)) {
            $resourceAuditedMetadata = new ResourceMetadata(
                    $resourceClassMetadata->getShortName().'Audit',
                    $resourceClassMetadata->getDescription().' Audited',
                    $resourceClassMetadata->getIri(),
                    ['get' => ['method' => 'GET']],
                    ['get' => ['method' => 'GET']],
                    array_merge(['rev'], $resourceClassMetadata->getAttributes())
                );

            return $resourceAuditedMetadata;
        }


        return $resourceClassMetadata;
    }
}
