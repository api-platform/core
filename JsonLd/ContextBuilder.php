<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\JsonLd;

use Dunglas\JsonLdApiBundle\Mapping\ClassMetadataFactory;
use Symfony\Component\Routing\RouterInterface;

/**
 * JSON-LD Context Builder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContextBuilder
{
    const HYDRA_NS = 'http://www.w3.org/ns/hydra/core#';
    const RDF_NS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';
    const XML_NS = 'http://www.w3.org/2001/XMLSchema#';
    const OWL_NS = 'http://www.w3.org/2002/07/owl#';

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;
    /**
     * @var Resources
     */
    private $resources;

    public function __construct(RouterInterface $router, ClassMetadataFactory $classMetadataFactory, Resources $resources)
    {
        $this->router = $router;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->resources = $resources;
    }

    /**
     * Builds the JSON-LD context for the entrypoint.
     *
     * @return array
     */
    public function getEntrypointContext()
    {
        $context = $this->getBaseContext();

        foreach ($this->resources as $resource) {
            $context[$resource->getBeautifiedName()] = [
                '@id' => sprintf('Entrypoint/%s', lcfirst($resource->getShortName())),
                '@type' => '@id',
            ];
        }

        return $context;
    }

    /**
     * Builds the JSON-LD context for the API documentation.
     *
     * @return array
     */
    public function getApiDocumentationContext()
    {
        return array_merge(
            $this->getBaseContext(),
            [
                'rdf' => self::RDF_NS,
                'rdfs' => self::RDFS_NS,
                'xmls' => self::XML_NS,
                'owl' => self::OWL_NS,
                'domain' => ['@id' => 'rdfs:domain', '@type' => '@id'],
                'range' => ['@id' => 'rdfs:range', '@type' => '@id'],
                'subClassOf' => ['@id' => 'rdfs:subClassOf', '@type' => '@id'],
                'expects' => ['@id' => 'hydra:expects', '@type' => '@id'],
                'returns' => ['@id' => 'hydra:returns', '@type' => '@id'],
            ]
        );
    }

    /**
     * Builds the JSON-LD context for the given resource.
     *
     * @param Resource|null $resource
     *
     * @return array
     */
    public function getContext(Resource $resource = null)
    {
        $context = $this->getBaseContext();

        if ($resource) {
            $prefixedShortName = sprintf('#%s', $resource->getShortName());

            $attributes = $this->classMetadataFactory->getMetadataFor(
                $resource->getEntityClass(),
                $resource->getNormalizationGroups(),
                $resource->getDenormalizationGroups(),
                $resource->getValidationGroups()
            )->getAttributes();

            foreach ($attributes as $attributeName => $attribute) {
                $id = sprintf('%s/%s', $prefixedShortName, $attributeName);

                if ($attribute->isLink()) {
                    $context[$attributeName] = [
                        '@id' => $id,
                        '@type' => '@id',
                    ];
                } else {
                    $context[$attributeName] = $id;
                }
            }
        }

        return $context;
    }

    /**
     * Bootstrap a serialization context with the given resource.
     *
     * @param Resource $resource
     * @param array    $context
     *
     * @return array [array, array]
     */
    public function bootstrap(Resource $resource, array $context = [])
    {
        $data = [];
        if (!isset($context['json_ld_has_context'])) {
            $data['@context'] = $this->router->generate(
                'json_ld_api_context',
                ['shortName' => $resource->getShortName()]
            );
            $context['json_ld_has_context'] = true;
        }

        return [$context, $data];
    }

    /**
     * Bootstrap relation context.
     *
     * @param Resource $resource
     * @param $class
     *
     * @return array
     */
    public function bootstrapRelation(Resource $resource, $class)
    {
        return [
            'resource' => $this->resources->getResourceForEntity($class),
            'json_ld_has_context' => true,
            'json_ld_normalization_groups' => $resource->getNormalizationGroups(),
            'json_ld_denormalization_groups' => $resource->getDenormalizationGroups(),
            'json_ld_validation_groups' => $resource->getValidationGroups(),
        ];
    }

    /**
     * Gets the base context.
     *
     * @return array
     */
    private function getBaseContext()
    {
        return [
            '@vocab' => $this->router->generate('json_ld_api_vocab', [], RouterInterface::ABSOLUTE_URL).'#',
            'hydra' => self::HYDRA_NS,
        ];
    }
}
