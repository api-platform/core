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

namespace ApiPlatform\Hydra\State\Util;

use ApiPlatform\Hydra\PartialCollectionView;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\IriHelper;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;

trait PaginationHelperTrait
{
    private function getPaginationIri(array $parsed, ?float $currentPage, ?float $lastPage, ?float $itemsPerPage, ?float $pageTotalItems, ?int $urlGenerationStrategy, string $pageParameterName): array
    {
        $first = $last = $previous = $next = null;

        if (null !== $lastPage) {
            $first = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $pageParameterName, 1., $urlGenerationStrategy);
            $last = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $pageParameterName, $lastPage, $urlGenerationStrategy);
        }

        if (1. !== $currentPage) {
            $previous = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $pageParameterName, $currentPage - 1., $urlGenerationStrategy);
        }

        if ((null !== $lastPage && $currentPage < $lastPage) || (null === $lastPage && $pageTotalItems >= $itemsPerPage)) {
            $next = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $pageParameterName, $currentPage + 1., $urlGenerationStrategy);
        }

        return [
            'first' => $first,
            'last' => $last,
            'previous' => $previous,
            'next' => $next,
        ];
    }

    private function getPartialCollectionView(mixed $object, string $requestUri, string $pageParameterName, string $enabledParameterName, ?int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH): PartialCollectionView
    {
        $currentPage = $lastPage = $itemsPerPage = $pageTotalItems = null;
        $paginated = false;
        if ($object instanceof PartialPaginatorInterface) {
            $paginated = true;
            if ($object instanceof PaginatorInterface) {
                $paginated = 1. !== $lastPage = $object->getLastPage();
            } else {
                $itemsPerPage = $object->getItemsPerPage();
                $pageTotalItems = (float) \count($object);
            }
            $currentPage = $object->getCurrentPage();
        }

        $parsed = IriHelper::parseIri($requestUri, $pageParameterName);
        $appliedFilters = $parsed['parameters'];
        unset($appliedFilters[$enabledParameterName]);

        $id = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $pageParameterName, $paginated ? $currentPage : null, $urlGenerationStrategy);

        if (!$paginated && $appliedFilters) {
            return new PartialCollectionView($id);
        }

        ['first' => $first, 'last' => $last, 'previous' => $previous, 'next' => $next] = $this->getPaginationIri($parsed, $currentPage, $lastPage, $itemsPerPage, $pageTotalItems, $urlGenerationStrategy, $pageParameterName);

        if (!$paginated) {
            $first = null;
            $last = null;
        }

        return new PartialCollectionView(
            $id,
            $first,
            $last,
            $previous,
            $next
        );
    }
}
