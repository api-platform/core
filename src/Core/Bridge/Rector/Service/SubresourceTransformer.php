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

namespace ApiPlatform\Core\Bridge\Rector\Service;

use ApiPlatform\Core\Util\Inflector;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as ODMAnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException as ODMMappingException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;

final class SubresourceTransformer
{
    private $ormMetadataFactory;
    private $odmMetadataFactory;

    public function __construct()
    {
        $this->ormMetadataFactory = new AnnotationDriver(new AnnotationReader());
        $this->odmMetadataFactory = new ODMAnnotationDriver(new AnnotationReader());
    }

    public function toUriVariables(array $subresourceMetadata): array
    {
        $uriVariables = [];
        $toClass = $subresourceMetadata['resource_class'];
        $fromProperty = $subresourceMetadata['property'];

        foreach (array_reverse($subresourceMetadata['identifiers']) as $identifier => $identifiedBy) {
            [$fromClass, $fromIdentifier, $fromPathVariable] = $identifiedBy;
            $fromClassMetadata = $this->getDoctrineMetadata($fromClass);
            $fromClassMetadataAssociationMappings = $fromClassMetadata->getAssociationMappings();

            $uriVariables[$identifier] = [
                'from_class' => $fromClass,
                'inverse_property' => null,
                'from_property' => null,
                'to_class' => $toClass,
                'property' => null,
                'to_property' => null,
                'identifiers' => $fromPathVariable ? [$fromIdentifier] : [],
                'composite_identifier' => false,
                'expanded_value' => $fromPathVariable ? null : Inflector::tableize($identifier),
            ];

            if ($toClass === $fromClass){
                $fromProperty = $identifier;
                continue;
            }

            $toClass = $fromClass;

            if (isset($fromProperty, $fromClassMetadataAssociationMappings[$fromProperty])) {
                $uriVariables[$identifier]['inverse_property'] = $fromProperty;
                $uriVariables[$identifier]['from_property'] = $fromProperty;
                $fromProperty = $identifier;
                continue;
            }
        }

        return array_reverse($uriVariables);
    }

    private function getDoctrineMetadata(string $class): ClassMetadata
    {
        $metadata = new ClassMetadata($class);
        $metadata->initializeReflection(new RuntimeReflectionService());

        try {
            $this->ormMetadataFactory->loadMetadataForClass($class, $metadata);
        } catch (MappingException $e) {
        }

//        try {
//            $this->odmMetadataFactory->loadMetadataForClass($class, $metadata);
//        } catch (ODMMappingException $e) {
//        }

        return $metadata;
    }
}
