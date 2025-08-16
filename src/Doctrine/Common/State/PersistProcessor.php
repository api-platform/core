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
        if ($operation instanceof HttpOperation && 'PUT' === $operation->getMethod() && ($operation->getExtraProperties()['standard_put'] ?? true)) {
            \assert(method_exists($manager, 'getReference'));
            $newData = $data;
            $identifiers = array_reverse($uriVariables);
            $links = $this->getLinks($class, $operation, $context);
            $reflectionProperties = $this->getReflectionProperties($data);

            // TODO: the call to getReference is most likely to fail with complex identifiers
            if ($previousData = $context['previous_data']) {
                $classMetadata = $manager->getClassMetadata($class);
                $identifiers = $classMetadata->getIdentifierValues($previousData);
                $newData = 1 === \count($identifiers) ? $manager->getReference($class, current($identifiers)) : clone $previousData;

                foreach ($reflectionProperties as $propertyName => $reflectionProperty) {
                    // // Don't override the property if it's part of the subresource system
                    if (isset($identifiers[$propertyName]) || isset($uriVariables[$propertyName])) {
                        continue;
                    }

                    // Skip URI variables as sometime an uri variable is not the doctrine identifier
                    foreach ($links as $link) {
                        if (\in_array($propertyName, $link->getIdentifiers(), true)) {
                            continue 2;
                        }
                    }

                    if (($newValue = $reflectionProperty->getValue($data)) !== $reflectionProperty->getValue($newData)) {
                        $reflectionProperty->setValue($newData, $newValue);
                    }
                }
            } else {
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
    private function isDeferredExplicit(DoctrineObjectManager $manager, object $data): bool
    {
        $classMetadata = $manager->getClassMetadata($this->getObjectClass($data));
        if ($classMetadata && method_exists($classMetadata, 'isChangeTrackingDeferredExplicit')) { // @phpstan-ignore-line metadata can be null
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
        $r = new \ReflectionObject($data);

        do {
            $props = $r->getProperties(~\ReflectionProperty::IS_STATIC);

            foreach ($props as $prop) {
                $ret[$prop->getName()] = $prop;
            }
        } while ($r = $r->getParentClass());

        return $ret;
    }
}
