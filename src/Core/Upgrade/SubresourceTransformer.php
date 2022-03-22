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

namespace ApiPlatform\Core\Upgrade;

use ApiPlatform\Util\Inflector;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as ODMAnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException as ODMMappingException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;

final class SubresourceTransformer
{
    private $ormMetadataFactory;
    private $odmMetadataFactory;

    public function __construct()
    {
        $this->ormMetadataFactory = class_exists(AnnotationDriver::class) ? new AnnotationDriver(new AnnotationReader()) : null;
        $this->odmMetadataFactory = class_exists(ODMAnnotationDriver::class) ? new ODMAnnotationDriver(new AnnotationReader()) : null;
    }

    public function toUriVariables(array $subresourceMetadata): array
    {
        $uriVariables = [];
        $toClass = $subresourceMetadata['resource_class'];
        $fromProperty = $subresourceMetadata['property'];

        foreach (array_reverse($subresourceMetadata['identifiers']) as $identifier => $identifiedBy) {
            [$fromClass, $fromIdentifier, $fromPathVariable] = $identifiedBy;
            $fromClassMetadata = $this->getDoctrineMetadata($fromClass);
            $fromClassMetadataAssociationMappings = $fromClassMetadata->associationMappings;

            $uriVariables[$identifier] = [
                'from_class' => $fromClass,
                'from_property' => null,
                'to_property' => null,
                'identifiers' => $fromPathVariable ? [$fromIdentifier] : [],
                'composite_identifier' => false,
                'expanded_value' => $fromPathVariable ? null : Inflector::tableize($identifier),
            ];

            if ($toClass === $fromClass) {
                $fromProperty = $identifier;
                continue;
            }

            $toClass = $fromClass;

            if (isset($fromProperty, $fromClassMetadataAssociationMappings[$fromProperty])) {
                $type = $fromClassMetadataAssociationMappings[$fromProperty]['type'];
                if (((class_exists(ODMClassMetadata::class) && ODMClassMetadata::MANY === $type) || (\is_int($type) && $type & ClassMetadataInfo::TO_MANY)) && isset($fromClassMetadataAssociationMappings[$fromProperty]['mappedBy'])) {
                    $uriVariables[$identifier]['to_property'] = $fromClassMetadataAssociationMappings[$fromProperty]['mappedBy'];
                    $fromProperty = $identifier;
                    continue;
                }
                $uriVariables[$identifier]['from_property'] = $fromProperty;
                $fromProperty = $identifier;
            }
        }

        return array_reverse($uriVariables);
    }

    /**
     * @return ODMClassMetadata|ClassMetadata
     */
    private function getDoctrineMetadata(string $class)
    {
        if ($this->odmMetadataFactory && class_exists(ODMClassMetadata::class)) {
            $isDocument = true;
            $metadata = new ODMClassMetadata($class);

            try {
                $this->odmMetadataFactory->loadMetadataForClass($class, $metadata);
            } catch (ODMMappingException $e) {
                $isDocument = false;
            }

            if ($isDocument) {
                return $metadata;
            }
        }

        $metadata = new ClassMetadata($class);
        $metadata->initializeReflection(new RuntimeReflectionService());

        try {
            if ($this->ormMetadataFactory) {
                $this->ormMetadataFactory->loadMetadataForClass($class, $metadata);
            }
        } catch (MappingException $e) {
        }

        return $metadata;
    }
}
