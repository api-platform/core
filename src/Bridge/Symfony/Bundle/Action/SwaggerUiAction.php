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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Action;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Displays the documentation in Swagger UI.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SwaggerUiAction
{
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $serializer;
    private $twig;
    private $title;
    private $description;
    private $version;
    private $formats = [];

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerInterface $serializer, \Twig_Environment $twig, string $title = '', string $description = '', string $version = '', array $formats = [])
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->serializer = $serializer;
        $this->twig = $twig;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->formats = $formats;
    }

    public function __invoke(Request $request)
    {
        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version, $this->formats);

        return new Response($this->twig->render(
            'ApiPlatformBundle:SwaggerUi:index.html.twig',
            $this->getContext($request) + ['spec' => $this->serializer->serialize($documentation, 'json')])
        );
    }

    /**
     * Gets the base Twig context.
     *
     * @param Request $request
     *
     * @return array
     */
    private function getContext(Request $request): array
    {
        $context = [
            'title' => $this->title,
            'description' => $this->description,
            'formats' => $this->formats,
            'shortName' => null,
            'operationId' => null,
        ];

        if (!$request->isMethodSafe(false) || null === $resourceClass = $request->attributes->get('_api_resource_class')) {
            return $context;
        }

        $metadata = $this->resourceMetadataFactory->create($resourceClass);
        $context['shortName'] = $metadata->getShortName();

        if (null !== $collectionOperationName = $request->attributes->get('_api_collection_operation_name')) {
            $context['operationId'] = sprintf('%s%sCollection', $collectionOperationName, $context['shortName']);
        } elseif (null !== $itemOperationName = $request->attributes->get('_api_item_operation_name')) {
            $context['operationId'] = sprintf('%s%sItem', $itemOperationName, $context['shortName']);
        }

        return $context;
    }
}
