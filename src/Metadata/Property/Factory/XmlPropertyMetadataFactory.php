<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\XmlExtractor;

/**
 * Creates a property metadata from XML {@see Property} configuration.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class XmlPropertyMetadataFactory extends AbstractFilePropertyMetadataFactory
{
    private $extractor;

    public function __construct(XmlExtractor $extractor, PropertyMetadataFactoryInterface $decorated = null)
    {
        parent::__construct($decorated);

        $this->extractor = $extractor;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMetadata(string $resourceClass, string $propertyName): array
    {
        return $this->extractor->getResources()[$resourceClass]['properties'][$propertyName] ?? [];
    }
}
