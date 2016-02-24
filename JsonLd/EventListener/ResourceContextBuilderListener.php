<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\EventListener;

use Dunglas\ApiBundle\JsonLd\Event\ContextBuilderEvent;
use Dunglas\ApiBundle\JsonLd\Event\Events;
use Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface;
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

        $attributes = $this->classMetadataFactory->getMetadataFor(
            $resource->getEntityClass(),
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        )->getAttributes();

        foreach ($attributes as $attributeName => $attribute) {
            if ($attribute->isIdentifier()) {
                continue;
            }
            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($attributeName) : $attributeName;

            if (!$id = $attribute->getIri()) {
                $id = sprintf('%s/%s', $prefixedShortName, $convertedName);
            }

            if ($attribute->isNormalizationLink()) {
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
