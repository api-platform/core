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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonApiUriTemplateCar',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_uri_template_cars',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_uri_template_cars/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonapi_uri_template_cars',
            processor: [self::class, 'process'],
        ),
        new GetCollection(
            uriTemplate: '/jsonapi_uri_template_brands/renault/cars',
            itemUriTemplate: '/jsonapi_uri_template_brands/renault/cars/{id}',
            provider: [self::class, 'provideCollection'],
        ),
        new Post(
            uriTemplate: '/jsonapi_uri_template_brands/renault/cars',
            itemUriTemplate: '/jsonapi_uri_template_brands/renault/cars/{id}',
            processor: [self::class, 'process'],
        ),
        new Get(
            uriTemplate: '/jsonapi_uri_template_brands/renault/cars/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class UriTemplateCar
{
    #[ApiProperty(identifier: true)]
    public string $id;

    public string $owner;

    public function __construct(string $id = '1', string $owner = 'Vincent')
    {
        $this->id = $id;
        $this->owner = $owner;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self((string) ($uriVariables['id'] ?? '1'), 'Vincent');
    }

    public static function provideCollection(): array
    {
        return [new self('1'), new self('2')];
    }

    public static function process(self $data): self
    {
        $data->id = '42';

        return $data;
    }
}
