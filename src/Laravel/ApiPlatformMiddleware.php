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

namespace ApiPlatform\Laravel;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPlatformMiddleware
{
    public function __construct(
        protected OperationMetadataFactory $operationMetadataFactory,
    ) {
    }

    /**
     * @param \Closure(Request): (Response) $next
     */
    public function handle(Request $request, \Closure $next, ?string $operationName = null): Response
    {
        $operation = null;
        if ($operationName) {
            $request->attributes->set('_api_operation', $operation = $this->operationMetadataFactory->create($operationName));
        }

        if (!($format = $request->route('_format')) && $operation instanceof HttpOperation && str_ends_with($operation->getUriTemplate(), '{._format}')) {
            $matches = [];
            if (preg_match('/\.[a-zA-Z]+$/', $request->getPathInfo(), $matches)) {
                $format = $matches[0];
            }
        }

        $request->attributes->set('_format', $format ? substr($format, 1, \strlen($format) - 1) : '');

        return $next($request);
    }
}
