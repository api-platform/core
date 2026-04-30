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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Parses the Range request header and converts it to pagination filters.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc9110#section-14.2
 *
 * @author Julien Robic <nayte91@gmail.com>
 */
final class RangeHeaderProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $decorated,
        private readonly Pagination $pagination,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'] ?? null;

        if (
            !$request
            || !$operation instanceof CollectionOperationInterface
            || !$operation instanceof HttpOperation
            || !\in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || !$request->headers->has('Range')
        ) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $rangeHeader = $request->headers->get('Range');

        if (!preg_match('/^([a-z]+)=(\d+)-(\d+)$/i', $rangeHeader, $matches)) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        [, $unit, $startStr, $endStr] = $matches;
        $expectedUnit = self::extractRangeUnit($operation);

        if (strtolower($unit) !== $expectedUnit) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $start = (int) $startStr;
        $end = (int) $endStr;

        if ($start > $end) {
            throw new HttpException(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, 'Range start must not exceed end.');
        }

        $itemsPerPage = $end - $start + 1;

        if (0 !== $start % $itemsPerPage) {
            throw new HttpException(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, 'Range must be aligned to page boundaries.');
        }

        $page = (int) ($start / $itemsPerPage) + 1;

        $options = $this->pagination->getOptions();
        $filters = $request->attributes->get('_api_filters', []);
        $filters[$options['page_parameter_name']] = $page;
        $filters[$options['items_per_page_parameter_name']] = $itemsPerPage;
        $request->attributes->set('_api_filters', $filters);

        $operation = $operation->withStatus(Response::HTTP_PARTIAL_CONTENT);
        $request->attributes->set('_api_operation', $operation);

        return $this->decorated->provide($operation, $uriVariables, $context);
    }

    /**
     * Extracts the range unit from the operation's uriTemplate (e.g., "/books{._format}" → "books").
     * Falls back to lowercase shortName, then "items".
     */
    private static function extractRangeUnit(HttpOperation $operation): string
    {
        if ($uriTemplate = $operation->getUriTemplate()) {
            $path = strtok($uriTemplate, '{');
            $segments = array_filter(explode('/', trim($path, '/')));
            if ($last = end($segments)) {
                return strtolower($last);
            }
        }

        return strtolower($operation->getShortName() ?? 'items') ?: 'items';
    }
}
