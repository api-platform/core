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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;

/**
 * Creates document's metadata using the mapping configuration.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ConfiguredDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    private $mapping;
    private $decorated;

    public function __construct(array $mapping, ?DocumentMetadataFactoryInterface $decorated = null)
    {
        $this->mapping = $mapping;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): DocumentMetadata
    {
        $indexMetadata = null;

        if ($this->decorated) {
            try {
                $indexMetadata = $this->decorated->create($resourceClass);
            } catch (IndexNotFoundException $e) {
            }
        }

        if (null === $index = $this->mapping[$resourceClass] ?? null) {
            if ($indexMetadata) {
                return $indexMetadata;
            }

            throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
        }

        $indexMetadata = $indexMetadata ?? new DocumentMetadata();

        if (isset($index['index'])) {
            $indexMetadata = $indexMetadata->withIndex($index['index']);
        }

        if (isset($index['type'])) {
            $indexMetadata = $indexMetadata->withType($index['type']);
        }

        return $indexMetadata;
    }
}
