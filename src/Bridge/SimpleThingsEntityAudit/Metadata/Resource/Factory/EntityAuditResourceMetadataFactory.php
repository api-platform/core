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
        $resourceClassMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (true === $this->auditManager->getMetadataFactory()->isAudited($resourceClass)) {
            $resourceAuditedMetadata = new ResourceMetadata(
                    $resourceClassMetadata->getShortName(),
                    $resourceClassMetadata->getDescription(),
                    $resourceClassMetadata->getIri(),
                    array_merge(is_array($resourceClassMetadata->getItemOperations()) ? $resourceClassMetadata->getItemOperations() : ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT'], 'delete' => ['method' => 'DELETE']], ['audits' => ['method' => 'GET', 'path' => '/audits/'.strtolower($resourceClassMetadata->getShortName()).'_audits/{id}']]),
                    array_merge(is_array($resourceClassMetadata->getCollectionOperations()) ? $resourceClassMetadata->getCollectionOperations() : ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']], ['audits' => ['method' => 'GET', 'path' => '/audits/'.strtolower($resourceClassMetadata->getShortName()).'_audits']]),
                    array_merge(['rev'], $resourceClassMetadata->getAttributes())
                );

            return $resourceAuditedMetadata;
        }


        return $resourceClassMetadata;
    }
}
