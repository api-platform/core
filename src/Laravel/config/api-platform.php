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

return [
    'title' => 'API Platform',
    'description' => 'My awesome API',
    'version' => '1.0.0',

    /*
     *  Automatic registration of routes will only happen if this setting is `true`
     */
    'register_routes' => true,
    'prefix' => '/api',

    /*
     * Where are ApiResource defined
     * TODO: link the docs on how to plug on eloquent models or create apiResource like controllers :D
     */
    'resources' => [
        app_path('Models'),
    ],

    'formats' => [
        'jsonld' => ['application/ld+json'],
        'jsonapi' => ['application/vnd.api+json'],
    ],

    'patch_formats' => [
        'json' => ['application/merge-patch+json'],
    ],

    'docs_formats' => [
        'jsonopenapi' => ['application/vnd.openapi+json'],
        'json' => ['application/json'],
        'jsonld' => ['application/ld+json'],
        'html' => ['text/html'],
    ],

    'error_formats' => [
        'jsonproblem' => ['application/problem+json'],
    ],

    'collection' => [
        'pagination' => [
            'enabled' => true,
            'partial' => false,
            'client_enabled' => false,
            'client_items_per_page' => false,
            'client_partial' => false,
            'items_per_page' => 30,
            'maximum_items_per_page' => null,
            'page_parameter_name' => 'page',
            'enabled_parameter_name' => 'pagination',
            'items_per_page_parameter_name' => 'itemsPerPage',
            'partial_parameter_name' => 'partial',
        ],
        'order' => [
            'parameter_name' => 'order',
        ],
    ],
];
