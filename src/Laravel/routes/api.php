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

use ApiPlatform\JsonLd\Action\ContextAction;
use ApiPlatform\Laravel\ApiPlatformMiddleware;
use ApiPlatform\Laravel\Controller\ApiPlatformController;
use ApiPlatform\Laravel\Controller\DocumentationController;
use ApiPlatform\Laravel\Controller\EntrypointController;
use ApiPlatform\Laravel\GraphQl\Controller\EntrypointController as GraphQlEntrypointController;
use ApiPlatform\Laravel\GraphQl\Controller\GraphiQlController;
use ApiPlatform\Metadata\Exception\NotExposedHttpException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

$globalMiddlewares = config()->get('api-platform.routes.middleware', []);
$domain = config()->get('api-platform.routes.domain');

Route::domain($domain)->middleware($globalMiddlewares)->group(function (): void {
    $resourceNameCollectionFactory = app()->make(ResourceNameCollectionFactoryInterface::class);
    $resourceMetadataFactory = app()->make(ResourceMetadataCollectionFactoryInterface::class);

    foreach ($resourceNameCollectionFactory->create() as $resourceClass) {
        foreach ($resourceMetadataFactory->create($resourceClass) as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() as $operation) {
                /* @var HttpOperation $operation */
                Route::addRoute($operation->getMethod(), Str::replace('{._format}', '{_format?}', $operation->getUriTemplate()), ApiPlatformController::class)
                    ->prefix($operation->getRoutePrefix())
                    ->middleware(ApiPlatformMiddleware::class.':'.$operation->getName())
                    ->middleware($operation->getMiddleware())
                    ->where('_format', '^\.[a-zA-Z]+')
                    ->name($operation->getName())
                    ->setDefaults(['_api_operation_name' => $operation->getName(), '_api_resource_class' => $operation->getClass()]);
            }
        }
    }

    $prefix = config()->get('api-platform.defaults.route_prefix') ?? '';

    Route::group(['prefix' => $prefix], function (): void {
        Route::group(['middleware' => ApiPlatformMiddleware::class], function (): void {
            Route::get('/contexts/{shortName?}{_format?}', ContextAction::class)
                ->middleware(ApiPlatformMiddleware::class)
                ->name('api_jsonld_context');

            Route::get('/docs{_format?}', DocumentationController::class)
                ->middleware(ApiPlatformMiddleware::class)
                ->name('api_doc');

            Route::get('/.well-known/genid/{id}', fn () => throw new NotExposedHttpException('This route is not exposed on purpose. It generates an IRI for a collection resource without identifier nor item operation.'))
                ->middleware(ApiPlatformMiddleware::class)
                ->name('api_genid');

            Route::get('/{index?}{_format?}', EntrypointController::class)
                ->where('index', 'index')
                ->middleware(ApiPlatformMiddleware::class)
                ->name('api_entrypoint');
        });

        if (config()->get('api-platform.graphql.enabled')) {
            Route::addRoute(['POST', 'GET'], '/graphql', GraphQlEntrypointController::class)
                ->name('api_graphql');

            Route::get('/graphiql', GraphiQlController::class)
                ->name('api_graphiql');
        }
    });
});
