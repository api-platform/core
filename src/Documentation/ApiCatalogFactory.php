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

namespace ApiPlatform\Documentation;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\WebLink\Link;

/**
 * Builds the links of the API catalog document.
 *
 * The catalog is anchored on the API entrypoint and advertises the machine-readable
 * description of the API ("service-desc"), its human-readable documentation
 * ("service-doc"), its metadata ("service-meta") and the exposed collections ("item").
 *
 * @see https://www.rfc-editor.org/rfc/rfc9727.html
 * @see https://www.rfc-editor.org/rfc/rfc8631.html
 *
 * @author Florent Morselli <florent.morselli@spomky-labs.com>
 */
final class ApiCatalogFactory
{
    public const ROUTE_NAME = 'api_catalog';

    /**
     * The profile identifying an "application/linkset+json" document as an API catalog.
     */
    public const PROFILE = 'https://www.rfc-editor.org/info/rfc9727';

    /**
     * Documentation formats, mapped to the link relation type they are described by.
     */
    private const DOCUMENTATION_RELATIONS = [
        'jsonopenapi' => 'service-desc',
        'yamlopenapi' => 'service-desc',
        'jsonld' => 'service-meta',
        'html' => 'service-doc',
    ];

    /**
     * @param array<string, string[]> $docsFormats
     */
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly IriConverterInterface $iriConverter,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly array $docsFormats = [],
        private readonly bool $docsEnabled = true,
    ) {
    }

    public function getUrl(): string
    {
        return $this->urlGenerator->generate(self::ROUTE_NAME, [], UrlGeneratorInterface::ABS_URL);
    }

    /**
     * @return Link[]
     */
    public function create(): array
    {
        $catalog = $this->getUrl();
        $entrypoint = $this->urlGenerator->generate('api_entrypoint', [], UrlGeneratorInterface::ABS_URL);

        $links = [(new Link('item', $entrypoint))->withAttribute('anchor', $catalog)];

        if ($this->docsEnabled) {
            foreach (self::DOCUMENTATION_RELATIONS as $format => $rel) {
                if (!$mimeTypes = $this->docsFormats[$format] ?? null) {
                    continue;
                }

                // The human-readable documentation is content negotiated, the other ones are explicit
                $parameters = 'html' === $format ? [] : ['_format' => $format];

                $links[] = (new Link($rel, $this->urlGenerator->generate('api_doc', $parameters, UrlGeneratorInterface::ABS_URL)))
                    ->withAttribute('anchor', $entrypoint)
                    ->withAttribute('type', $mimeTypes[array_key_first($mimeTypes)]);
            }
        }

        foreach ($this->getCollectionIris() as $iri) {
            $links[] = (new Link('item', $iri))->withAttribute('anchor', $entrypoint);
        }

        return $links;
    }

    /**
     * @return iterable<string>
     */
    private function getCollectionIris(): iterable
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $seen = [];

            foreach ($this->resourceMetadataFactory->create($resourceClass) as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    $shortName = $resource->getShortName();

                    if (true === $operation->getHideHydraOperation() || !$operation instanceof CollectionOperationInterface || isset($seen[$shortName])) {
                        continue;
                    }

                    try {
                        $iri = $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_URL, $operation);
                    } catch (InvalidArgumentException|OperationNotFoundException) {
                        // Ignore resources without GET operations
                        continue;
                    }

                    $seen[$shortName] = true;

                    yield $iri;
                }
            }
        }
    }
}
