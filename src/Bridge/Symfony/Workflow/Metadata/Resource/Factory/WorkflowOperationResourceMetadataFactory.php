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

namespace ApiPlatform\Core\Bridge\Symfony\Workflow\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Adds two operation to resource classes supporting Workflows:
 * - PATCH to update the workflow state
 * - GET to get possible states for a given resource.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class WorkflowOperationResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $supportsWorkflow;
    private $decorated;

    public function __construct(array $supportsWorkflow = [], ResourceMetadataFactoryInterface $decorated)
    {
        $this->supportsWorkflow = $supportsWorkflow;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        if (!\in_array($resourceClass, $this->supportsWorkflow, true)) {
            return $resourceMetadata;
        }

        $operations = $resourceMetadata->getItemOperations();

        $operations['workflow_state_patch'] = [
            'method' => 'PATCH',
            '_path_suffix' => '/state',
        ];

        $operations['workflow_state_get'] = [
            'method' => 'GET',
            '_path_suffix' => '/state',
        ];

        return $resourceMetadata->withItemOperations($operations);
    }
}
