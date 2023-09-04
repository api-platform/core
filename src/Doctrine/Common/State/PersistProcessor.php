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

namespace ApiPlatform\Doctrine\Common\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager as DoctrineObjectManager;

final class PersistProcessor implements ProcessorInterface
{
    use ClassInfoTrait;
    use LinksHandlerTrait;

    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @template T
     *
     * @param T $data
     *
     * @return T
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (
            !\is_object($data)
            || !$manager = $this->managerRegistry->getManagerForClass($class = $this->getObjectClass($data))
        ) {
            return $data;
        }

        // PUT: reset the existing object managed by Doctrine and merge data sent by the user in it
        // This custom logic is needed because EntityManager::merge() has been deprecated and UPSERT isn't supported:
        // https://github.com/doctrine/orm/issues/8461#issuecomment-1250233555
        if ($operation instanceof HttpOperation && 'PUT' === $operation->getMethod() && ($operation->getExtraProperties()['standard_put'] ?? false)) {
            \assert(method_exists($manager, 'getReference'));
            // TODO: the call to getReference is most likely to fail with complex identifiers
            $newData = $data;
            if ($previousData = $context['previous_data']) {
                $newData = 1 === \count($uriVariables) ? $manager->getReference($class, current($uriVariables)) : clone $previousData;
            }

            $identifiers = array_reverse($uriVariables);
            $links = $this->getLinks($class, $operation, $context);
            $reflectionProperties = $this->getReflectionProperties($data);

            if (!$previousData) {
                foreach (array_reverse($links) as $link) {
                    if ($link->getExpandedValue() || !$link->getFromClass()) {
                        continue;
                    }

                    $identifierProperties = $link->getIdentifiers();
                    $hasCompositeIdentifiers = 1 < \count($identifierProperties);

                    foreach ($identifierProperties as $identifierProperty) {
                        $reflectionProperty = $reflectionProperties[$identifierProperty];
                        $reflectionProperty->setValue($newData, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null));
                    }
                }
            } else {
                foreach ($reflectionProperties as $propertyName => $reflectionProperty) {
                    // Don't override the property if it's part of the subresource system
                    if (isset($uriVariables[$propertyName])) {
                        continue;
                    }

                    foreach ($links as $link) {
                        $identifierProperties = $link->getIdentifiers();
                        if (\in_array($propertyName, $identifierProperties, true)) {
                            continue;
                        }

                        if (($newValue = $reflectionProperty->getValue($data)) !== $reflectionProperty->getValue($newData)) {
                            $reflectionProperty->setValue($newData, $newValue);
                        }
                    }
                }
            }

            $data = $newData;
        }

        if (!$manager->contains($data) || $this->isDeferredExplicit($manager, $data)) {
            $manager->persist($data);
        }

        $manager->flush();
        $manager->refresh($data);

        return $data;
    }

    /**
     * Checks if doctrine does not manage data automatically.
     */
    private function isDeferredExplicit(DoctrineObjectManager $manager, $data): bool
    {
        $classMetadata = $manager->getClassMetadata($this->getObjectClass($data));
        if (method_exists($classMetadata, 'isChangeTrackingDeferredExplicit')) {
            return $classMetadata->isChangeTrackingDeferredExplicit();
        }

        return false;
    }

    /**
     * Get reflection properties indexed by property name.
     *
     * @return array<string, \ReflectionProperty>
     */
    private function getReflectionProperties(mixed $data): array
    {
        $ret = [];
        $props = (new \ReflectionObject($data))->getProperties(~\ReflectionProperty::IS_STATIC);

        foreach ($props as $prop) {
            $ret[$prop->getName()] = $prop;
        }

        return $ret;
    }
}
