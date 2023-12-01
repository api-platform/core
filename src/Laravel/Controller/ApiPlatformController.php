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

namespace ApiPlatform\Laravel\Controller;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApiPlatformController extends Controller
{
    public function __construct(
        protected OperationMetadataFactory $operationMetadataFactory,
        protected ProviderInterface $provider,
        protected ProcessorInterface $processor,
        protected Application $app,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function __invoke(Request $request)
    {
        $operation = $request->attributes->get('_api_operation');

        if (!$operation) {
            throw new \RuntimeException('Operation not found.');
        }

        $uriVariables = $this->getUriVariables($request, $operation);
        // at some point we could introduce that back
        // if ($this->uriVariablesConverter) {
        //     $context = ['operation' => $operation, 'uri_variables_map' => $uriVariablesMap];
        //     $identifiers = $this->uriVariablesConverter->convert($identifiers, $operation->getClass() ?? $resourceClass, $context);
        // }

        $context = [
            'request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
        ];

        if (null === $operation->canValidate()) {
            $operation = $operation->withValidate(!$request->isMethodSafe() && !$request->isMethod('DELETE'));
        }

        if (null === $operation->canRead() && $operation instanceof HttpOperation) {
            $operation = $operation->withRead($operation->getUriVariables() || $request->isMethodSafe());
        }

        if (null === $operation->canDeserialize() && $operation instanceof HttpOperation) {
            $operation = $operation->withDeserialize(\in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH'], true));
        }

        $body = $this->provider->provide($operation, $uriVariables, $context);

        // The provider can change the Operation, extract it again from the Request attributes
        if ($request->attributes->get('_api_operation') !== $operation) {
            $operation = $request->attributes->get('_api_operation');
            $uriVariables = $this->getUriVariables($request, $operation);
        }

        $context['previous_data'] = $request->attributes->get('previous_data');
        $context['data'] = $request->attributes->get('data');

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(!$request->isMethodSafe());
        }

        if (null === $operation->canSerialize()) {
            $operation = $operation->withSerialize(true);
        }

        return $this->processor->process($body, $operation, $uriVariables, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function getUriVariables(Request $request, HttpOperation $operation): array
    {
        $uriVariables = [];
        foreach ($operation->getUriVariables() ?? [] as $parameterName => $_) {
            $uriVariables[$parameterName] = $request->route($parameterName);
        }

        return $uriVariables;
    }
}
