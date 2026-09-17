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
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\RequestParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Serves paginated collections as HTTP range requests (RFC 9110 §14), opt-in per operation
 * through {@see HttpOperation::getRangeUnit()}.
 *
 * A range applies to a would-be 200 only (§14.2): it is ignored on other methods, other
 * units, unparseable specifiers and whenever the request fails before reading. Choices the
 * RFC leaves open: If-Range counts as a mismatch, collections carrying no validator
 * (§13.1.5); the 206 is promised only once a paginator came back, every 206 having to
 * carry a Content-Range (§15.3.7); a range pagination cannot serve as a page (misaligned,
 * wider than the maximum) gets a 416 rather than being silently ignored, so that clients
 * learn it.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc9110#section-14
 *
 * @author Julien Robic <nayte91@gmail.com>
 */
final class RangeHeaderProvider implements ProviderInterface
{
    /** Int-range "<first>-<last>" only (§14.1.2): pagination can serve neither an open-ended nor a suffix range. */
    private const RANGE_PATTERN = '/^(?<unit>[A-Za-z0-9!#$%&\'*+\-.^_`|~]+)=(?<first>\d+)-(?<last>\d+)$/';

    public function __construct(
        private readonly ProviderInterface $decorated,
        private readonly Pagination $pagination,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'] ?? null;

        if (
            !$request instanceof Request
            || !$operation instanceof HttpOperation
            || !$operation instanceof CollectionOperationInterface
            || null === ($unit = $operation->getRangeUnit())
            || !$request->isMethod('GET')
            || !$request->headers->has('Range')
            || $request->headers->has('If-Range')
            || !\in_array($operation->getStatus(), [null, Response::HTTP_OK], true)
            || !preg_match(self::RANGE_PATTERN, $request->headers->get('Range', ''), $range)
            || strtolower($range['unit']) !== strtolower($unit)
        ) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $first = (int) $range['first'];
        $last = (int) $range['last'];

        if ($first > $last) {
            throw new HttpException(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, \sprintf('The range first position (%d) must not exceed its last position (%d).', $first, $last));
        }

        $length = $last - $first + 1;
        $maximumItemsPerPage = $operation->getPaginationMaximumItemsPerPage() ?? $this->pagination->getOptions()['maximum_items_per_page'];

        if (null !== $maximumItemsPerPage && $length > $maximumItemsPerPage) {
            throw new HttpException(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, \sprintf('A range must not span more than %d %s.', $maximumItemsPerPage, $unit));
        }

        if (0 !== $first % $length) {
            throw new HttpException(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, \sprintf('The range first position must be a multiple of its length (%d).', $length));
        }

        $options = $this->pagination->getOptions();
        $filters = $request->attributes->get('_api_filters');
        if (null === $filters) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : [];
        }

        // Pagination::getLimit() ignores the items-per-page filter unless the client may choose it:
        // set the operation too, so that the range wins over the query string.
        $filters[$options['page_parameter_name']] = intdiv($first, $length) + 1;
        $filters[$options['items_per_page_parameter_name']] = $length;
        $request->attributes->set('_api_filters', $filters);

        $operation = $operation->withPaginationItemsPerPage($length);
        $request->attributes->set('_api_operation', $operation);

        $data = $this->decorated->provide($operation, $uriVariables, $context);

        if (!$data instanceof PartialPaginatorInterface) {
            return $data;
        }

        if ($data instanceof PaginatorInterface) {
            $totalItems = (int) $data->getTotalItems();

            if ($first >= $totalItems) {
                throw new HttpException(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, \sprintf('The range first position (%d) is beyond the collection (%d %s).', $first, $totalItems, $unit), null, ['Content-Range' => \sprintf('%s */%d', $unit, $totalItems)]);
            }
        } elseif (0 === \count($data)) {
            throw new HttpException(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, \sprintf('The range first position (%d) is beyond the collection.', $first));
        }

        $request->attributes->set('_api_operation', $operation->withStatus(Response::HTTP_PARTIAL_CONTENT));

        return $data;
    }
}
