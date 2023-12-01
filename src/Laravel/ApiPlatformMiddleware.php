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
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, \Closure $next, string $operationName = null): Response
    {
        if ($operationName) {
            $request->attributes->set('_api_operation', $this->operationMetadataFactory->create($operationName));
        }

        $request->attributes->set('_format', str_replace('.', '', $request->route('_format') ?? ''));

        return $next($request);
    }
}
