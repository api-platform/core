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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\IriHelper;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Util\CorsTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * A single-page collection still gets `first` and `last`, where the body links emit nothing: a HEAD response
 * (RFC 9110 §9.3.2) carries no body from which a client could derive them. Cursor pagination is out of
 * scope: RFC 8288 relations address pages, not cursors.
 *
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 *
 * @author Julien Robic <nayte91@gmail.com>
 */
final class PaginationLinkProcessor implements ProcessorInterface
{
    use CorsTrait;

    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(private readonly ProcessorInterface $decorated, private readonly Pagination $pagination)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (
            !($request = $context['request'] ?? null)
            || !$request instanceof Request
            || !$operation instanceof HttpOperation
            || !$operation instanceof CollectionOperationInterface
            || true !== $operation->getPaginationLinkHeader()
            || null !== $operation->getPaginationViaCursor()
            || $this->isPreflightRequest($request)
        ) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $paginator = $context['original_data'] ?? $data;
        if (!$paginator instanceof PartialPaginatorInterface) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $options = $this->pagination->getOptions();
        $pageParameterName = $options['page_parameter_name'];
        $parsed = IriHelper::parseIri($request->getUri(), $pageParameterName);
        $parameters = $parsed['parameters'];

        $itemsPerPage = $paginator->getItemsPerPage();

        $urlGenerationStrategy = $operation->getUrlGenerationStrategy() ?? UrlGeneratorInterface::ABS_PATH;
        $url = static fn (float $page): string => IriHelper::createIri($parsed['parts'], $parameters, $pageParameterName, $page, $urlGenerationStrategy);

        // REFACTOR-WHEN: a third consumer of the page-link math appears → move Hydra's PaginationHelperTrait to State\Util and share it
        $currentPage = $paginator->getCurrentPage();
        $lastPage = $paginator instanceof PaginatorInterface ? $paginator->getLastPage() : null;

        $pages = ['self' => $currentPage];
        if (null !== $lastPage) {
            $pages['first'] = 1.;
        }
        if ($currentPage > 1.) {
            $pages['prev'] = $currentPage - 1.;
        }
        if ((null !== $lastPage && $currentPage < $lastPage) || (null === $lastPage && \count($paginator) >= $itemsPerPage)) {
            $pages['next'] = $currentPage + 1.;
        }
        if (null !== $lastPage) {
            $pages['last'] = $lastPage;
        }

        $linkProvider = $request->attributes->get('_api_platform_links') ?? new GenericLinkProvider();
        foreach ($pages as $rel => $page) {
            $linkProvider = $linkProvider->withLink(new Link($rel, $url($page)));
        }
        $request->attributes->set('_api_platform_links', $linkProvider);

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
