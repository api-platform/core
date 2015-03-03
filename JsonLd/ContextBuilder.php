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
     * Builds the JSON-LD context for the given resource.
     *
     * @param Resource|null $resource
     *
     * @return array
     */
    public function buildContext(Resource $resource = null)
    {
        $context = [
            '@vocab' => $this->router->generate('json_ld_api_vocab', [], RouterInterface::ABSOLUTE_URL).'#',
            'hydra' => self::HYDRA_NS,
            'rdf' => self::RDF_NS,
            'rdfs' => self::RDFS_NS,
            'domain' => ['@id' => 'rdfs:domain', '@type' => '@id' ],
            'range' => ['@id' => 'rdfs:range', '@type' => '@id' ],
            'subClassOf' => ['@id' => 'rdfs:subClassOf', '@type' => '@id' ],
        ];

        if ($resource) {
            $attributes = $this->classMetadataFactory->getMetadataFor(
                $resource->getEntityClass(),
                $resource->getNormalizationGroups(),
                $resource->getDenormalizationGroups(),
                $resource->getValidationGroups()
            )->getAttributes();

            foreach ($attributes as $attributeName => $attribute) {
                if (isset($attribute->getTypes()[0]) && 'object' === $attribute->getTypes()[0]->getType()) {
                    $typeClass = $attribute->getTypes()[0]->getClass();
                    if (null === $this->resources->getResourceForEntity($typeClass) &&
                        null !== ($guessedType = $this->guessNativeType($typeClass))) {
                        $type = $guessedType;
                    } else {
                        $type = '@id';
                    }

                    $context[$attributeName] = ['@type' => $type];
                }
            }
        }

        return $context;
    }

    /**
     * Guess native class type.
     *
     * @param string $class
     *
     * @return null|string
     */
    private function guessNativeType($class)
    {
        if ('DateTime' === $class) {
            return self::XML_NS.'dateTime';
        }
    }
}
