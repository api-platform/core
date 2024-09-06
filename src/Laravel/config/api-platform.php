<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

return [
    'title' => 'API Platform',
    'description' => 'My awesome API',
    'version' => '1.0.0',

    'routes' => [
        'prefix' => '/api',
        'middleware' => [],
    ],

    /*
     * Where are ApiResource defined
     * TODO: link the docs on how to plug on eloquent models or create apiResource like controllers :D
     */
    'resources' => [
        app_path('Models'),
    ],

    'formats' => [
        'jsonld' => ['application/ld+json'],
        //'jsonapi' => ['application/vnd.api+json'],
    ],

    'patch_formats' => [
        'json' => ['application/merge-patch+json'],
    ],

    'docs_formats' => [
        'jsonld' => ['application/ld+json'],
        //'jsonapi' => ['application/vnd.api+json'],
        'jsonopenapi' => ['application/vnd.openapi+json'],
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

    'graphql' => [
        'enabled' => false,
        'nesting_separator' => '__',
        'introspection' => ['enabled' => true]
    ],

    'exception_to_status' => [
        AuthenticationException::class => 401,
        AuthorizationException::class => 403
    ],

    'swagger_ui' => [
        'enabled' => true
    ]
];
