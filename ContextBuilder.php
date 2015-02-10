<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle;

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

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;

    public function __construct(RouterInterface $router, ClassMetadataFactory $classMetadataFactory)
    {
        $this->router = $router;
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * Builds the JSON-LD context for the given resource.
     *
     * @param Resource $resource
     *
     * @return array
     */
    public function buildContext(Resource $resource)
    {
        $context = [];
        $context['@vocab'] = $this->router->generate('json_ld_api_vocab', [], RouterInterface::ABSOLUTE_URL).'#';
        $context['hydra'] = self::HYDRA_NS;

        $attributes = $this->classMetadataFactory->getMetadataFor($resource->getEntityClass())->getAttributes(
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        );

        foreach ($attributes as $attributeName => $data) {
            if ($data['type']) {
                $context[$attributeName] = ['@type' => '@id'];
            }
        }

        return $context;
    }
}
