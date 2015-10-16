<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\EventListener;

use Dunglas\ApiBundle\JsonLd\Event\ContextBuilderEvent;
use Dunglas\ApiBundle\JsonLd\Event\Events;
use Dunglas\ApiBundle\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Builds default context for JSON-LD resources.
 *
 * @author Luc Vieillescazes <luc@vieillescazes.net>
 */
class ResourceContextBuilderListener implements EventSubscriberInterface
{
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $classMetadataFactory;
    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    public function __construct(ClassMetadataFactoryInterface $classMetadataFactory, NameConverterInterface $nameConverter = null)
    {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->nameConverter = $nameConverter;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::CONTEXT_BUILDER => ['onContextBuilder'],
        ];
    }

    /**
     * Builds default context.
     *
     * @param ContextBuilderEvent $event
     */
    public function onContextBuilder(ContextBuilderEvent $event)
    {
        $resource = $event->getResource();

        if (null === $resource) {
            return;
        }

        $context = $event->getContext();

        $prefixedShortName = sprintf('#%s', $resource->getShortName());

        $classMetadata = $this->classMetadataFactory->getMetadataFor(
            $resource->getEntityClass(),
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        );

        $identifierName = $classMetadata->getIdentifierName();
        foreach ($classMetadata->getAttributesMetadata() as $attributeName => $attributeMetadata) {
            if ($identifierName === $attributeName) {
                continue;
            }
            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($attributeName) : $attributeName;

            if (!$id = $attributeMetadata->getIri()) {
                $id = sprintf('%s/%s', $prefixedShortName, $convertedName);
            }

            if ($attributeMetadata->isNormalizationLink()) {
                $context[$convertedName] = [
                    '@id' => $id,
                    '@type' => '@id',
                ];
            } else {
                $context[$convertedName] = $id;
            }
        }

        $event->setContext($context);
    }
}
