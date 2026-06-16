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

use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\Serializer\NameConverter\SnakeCaseToCamelCaseNameConverter;

return [
    'title' => 'API Platform',
    'description' => 'My awesome API',
    'version' => '1.0.0',
    'show_webby' => true,

    'routes' => [
        'domain' => null,
        // Global middleware applied to every API Platform routes
        // 'middleware' => [],
    ],

    'resources' => [
        app_path('Models'),
        app_path('ApiResource'),
    ],

    'formats' => [
        'jsonld' => ['application/ld+json'],
        'json' => ['application/json'],
        // 'jsonapi' => ['application/vnd.api+json'],
        // 'csv' => ['text/csv'],
    ],

    'patch_formats' => [
        'json' => ['application/merge-patch+json'],
    ],

    // When true, 'required' validation rules are replaced with 'sometimes'
    // on PATCH operations, allowing partial updates without requiring all fields.
    'partial_patch_validation' => false,

    'docs_formats' => [
        'jsonld' => ['application/ld+json'],
        // 'jsonapi' => ['application/vnd.api+json'],
        'jsonopenapi' => ['application/vnd.openapi+json'],
        'html' => ['text/html'],
    ],

    'error_formats' => [
        'jsonproblem' => ['application/problem+json'],
    ],

    'defaults' => [
        'pagination_enabled' => true,
        'pagination_partial' => false,
        'pagination_client_enabled' => false,
        'pagination_client_items_per_page' => false,
        'pagination_client_partial' => false,
        'pagination_items_per_page' => 30,
        'pagination_maximum_items_per_page' => 30,
        'route_prefix' => '/api',
        'middleware' => [],
    ],

    'pagination' => [
        'page_parameter_name' => 'page',
        'enabled_parameter_name' => 'pagination',
        'items_per_page_parameter_name' => 'itemsPerPage',
        'partial_parameter_name' => 'partial',
    ],

    'jsonapi' => [
        // When false, the JSON:API `data.id` uses the resource scalar identifier
        // and a `data.links.self` IRI is added. When true (default), `data.id`
        // is the resource IRI.
        'use_iri_as_id' => true,

        // Allow client-generated IDs on JSON:API POST per
        // https://jsonapi.org/format/#crud-creating-client-ids. Off by default
        // to avoid id spoofing on public endpoints.
        'allow_client_generated_id' => false,
    ],

    'graphql' => [
        'enabled' => false,
        'nesting_separator' => '__',
        'introspection' => ['enabled' => true],
        'max_query_complexity' => 500,
        'max_query_depth' => 200,
        // 'middleware' => null,
    ],

    'graphiql' => [
        // 'enabled' => true,
        // 'domain' => null,
        // 'middleware' => null,
    ],

    // set to null if you want to keep snake_case
    'name_converter' => SnakeCaseToCamelCaseNameConverter::class,

    'exception_to_status' => [
        AuthenticationException::class => 401,
        AuthorizationException::class => 403,
    ],

    'redoc' => [
        'enabled' => true,
    ],

    'scalar' => [
        'enabled' => true,
        'extra_configuration' => [],
    ],

    'swagger_ui' => [
        'enabled' => true,
        // 'apiKeys' => [
        //     'api' => [
        //         'name' => 'Authorization',
        //         'type' => 'header',
        //     ],
        // ],
        // 'oauth' => [
        //     'enabled' => true,
        //     'type' => 'oauth2',
        //     'flow' => 'authorizationCode',
        //     'tokenUrl' => '',
        //     'authorizationUrl' =>'',
        //     'refreshUrl' => '',
        //     'scopes' => ['scope1' => 'Description scope 1'],
        //     'pkce' => true,
        // ],
        // 'license' => [
        //     'name' => 'Apache 2.0',
        //     'url' => 'https://www.apache.org/licenses/LICENSE-2.0.html',
        // ],
        // 'contact' => [
        //     'name' => 'API Support',
        //     'url' => 'https://www.example.com/support',
        //     'email' => 'support@example.com',
        // ],
        // 'http_auth' => [
        //     'Personal Access Token' => [
        //         'scheme' => 'bearer',
        //         'bearerFormat' => 'JWT',
        //     ],
        // ],
    ],

    // 'openapi' => [
    //     'tags' => [],
    // ],

    'url_generation_strategy' => UrlGeneratorInterface::ABS_PATH,

    // Class implementing PathSegmentNameGeneratorInterface used to derive route
    // segments from resource short names (e.g. `ProductOrder` -> `product_orders`).
    // Set to DashPathSegmentNameGenerator::class for dasherized segments
    // (e.g. `product-orders`).
    'path_segment_name_generator' => UnderscorePathSegmentNameGenerator::class,

    'serializer' => [
        'hydra_prefix' => false,
        // 'datetime_format' => \DateTimeInterface::RFC3339,
    ],

    // we recommend using "file" or "acpu"
    'cache' => 'file',

    // Path to an Eloquent model metadata file produced by `php artisan api-platform:metadata:dump`.
    // When set (and APP_DEBUG is false), the model attributes and relations are read from this file
    // at boot instead of being introspected from the database, allowing the app to boot without a
    // live DB (e.g. during `docker build`, `composer install`, or static analysis in CI). Commit the
    // file to VCS or bake it into your image, and re-run the command when your models change. Leave
    // null to disable.
    'metadata_dump' => null,

    // MCP (Model Context Protocol) configuration
    'mcp' => [
        'enabled' => true,
    ],

    // install `api-platform/http-cache`
    // 'http_cache' => [
    //     'etag' => false,
    //     'max_age' => null,
    //     'shared_max_age' => null,
    //     'vary' => null,
    //     'public' => null,
    //     'stale_while_revalidate' => null,
    //     'stale_if_error' => null,
    //     'invalidation' => [
    //         'urls' => [],
    //         'scoped_clients' => [],
    //         'max_header_length' => 7500,
    //         'request_options' => [],
    //         'purger' => ApiPlatform\HttpCache\SouinPurger::class,
    //     ],
    // ],

    'error_handler' => [
        'extend_laravel_handler' => true,
    ],
];
