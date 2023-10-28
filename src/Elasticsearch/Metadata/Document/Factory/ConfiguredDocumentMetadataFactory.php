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

namespace ApiPlatform\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;

/**
 * Creates document's metadata using the mapping configuration.
 *
 * @deprecated
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ConfiguredDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    public function __construct(private readonly array $mapping, private readonly ?DocumentMetadataFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): DocumentMetadata
    {
        $documentMetadata = null;

        if ($this->decorated) {
            try {
                $documentMetadata = $this->decorated->create($resourceClass);
            } catch (IndexNotFoundException) {
            }
        }

        if (null === $index = $this->mapping[$resourceClass] ?? null) {
            if ($documentMetadata) {
                return $documentMetadata;
            }

            throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
        }

        $documentMetadata ??= new DocumentMetadata();

        if (isset($index['index'])) {
            $documentMetadata = $documentMetadata->withIndex($index['index']);
        }

        if (isset($index['type'])) {
            $documentMetadata = $documentMetadata->withType($index['type']);
        }

        return $documentMetadata;
    }
}
