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
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class ApiPlatformController extends Controller
{
    /**
     * @param ProviderInterface<object>                                  $provider
     * @param ProcessorInterface<iterable<object>|object|null, Response> $processor
     */
    public function __construct(
        protected OperationMetadataFactoryInterface $operationMetadataFactory,
        protected ProviderInterface $provider,
        protected ProcessorInterface $processor,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function __invoke(Request $request): Response
    {
        $operation = $request->attributes->get('_api_operation');
        if (!$operation) {
            throw new \RuntimeException('Operation not found.');
        }

        if (!$operation instanceof HttpOperation) {
            throw new \LogicException('Operation is not an HttpOperation.');
        }

        $uriVariables = $this->getUriVariables($request, $operation);
        $request->attributes->set('_api_uri_variables', $uriVariables);
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

        if (null === $operation->canRead()) {
            $operation = $operation->withRead($operation->getUriVariables() || $request->isMethodSafe());
        }

        if (null === $operation->canDeserialize()) {
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
            $parameter = $request->route($parameterName);
            if (\is_string($parameter) && ($format = $request->attributes->get('_format')) && str_contains($parameter, $format)) {
                $parameter = substr($parameter, 0, \strlen($parameter) - (\strlen($format) + 1));
            }

            $uriVariables[(string) $parameterName] = $parameter;
        }

        return $uriVariables;
    }
}
