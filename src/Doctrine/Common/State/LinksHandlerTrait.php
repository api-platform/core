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

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;

trait LinksHandlerTrait
{
    /**
     * @param HttpOperation|GraphQlOperation $operation
     *
     * @return Link[]
     */
    private function getLinks(string $resourceClass, Operation $operation, array $context): array
    {
        $links = ($operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables()) ?? [];

        if (!($linkClass = $context['linkClass'] ?? false)) {
            return $links;
        }

        $newLinks = [];

        foreach ($links as $link) {
            if ($linkClass === $link->getFromClass()) {
                $newLinks[] = $link;
            }
        }

        $operation = $this->resourceMetadataCollectionFactory->create($linkClass)->getOperation($operation->getName());
        foreach ($operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables() as $link) {
            if ($resourceClass === $link->getToClass()) {
                $newLinks[] = $link;
            }
        }

        if (!$newLinks) {
            throw new RuntimeException(sprintf('The class "%s" cannot be retrieved from "%s".', $resourceClass, $linkClass));
        }

        return $newLinks;
    }

    private function getIdentifierValue(array &$identifiers, string $name = null)
    {
        if (isset($identifiers[$name])) {
            $value = $identifiers[$name];
            unset($identifiers[$name]);

            return $value;
        }

        return array_shift($identifiers);
    }
}
