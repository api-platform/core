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

namespace Workbench\App\DynamicResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\TypeInfo\Type;

/**
 * Simulates resources defined at runtime (e.g. stored in a database): no PHP class per resource.
 */
final class DynamicResourceDefinitions
{
    public const FAQ = 'DynamicFaq';

    /**
     * @return array<string, array{uriTemplate: string, properties: array<string, ApiProperty>}>
     */
    public static function all(): array
    {
        return [
            self::FAQ => [
                'uriTemplate' => '/dynamic/faq',
                'properties' => [
                    'id' => new ApiProperty(identifier: true, readable: true, writable: false, nativeType: Type::int()),
                    'question' => new ApiProperty(readable: true, writable: true, required: true, nativeType: Type::string()),
                    'answer' => new ApiProperty(readable: true, writable: true, nativeType: Type::string()),
                ],
            ],
        ];
    }

    public static function has(string $resource): bool
    {
        return isset(self::all()[$resource]);
    }

    public static function resource(string $name): ApiResource
    {
        /** @var class-string $resource resource identified by a name which is not a class */
        $resource = $name;
        $definition = self::all()[$resource];
        $link = new Link(fromClass: $resource, identifiers: ['id'], parameterName: 'id');
        // global defaults (api-platform.defaults) are only applied to resources declared with attributes
        $routePrefix = config('api-platform.defaults.route_prefix');

        return new ApiResource(
            class: $resource,
            shortName: $resource,
            operations: [
                new Get(class: $resource, shortName: $resource, routePrefix: $routePrefix, uriTemplate: $definition['uriTemplate'].'/{id}', uriVariables: ['id' => $link], provider: [self::class, 'provide']),
                new GetCollection(class: $resource, shortName: $resource, routePrefix: $routePrefix, uriTemplate: $definition['uriTemplate'], provider: [self::class, 'provide']),
            ],
        );
    }

    /**
     * @param array<string, mixed> $uriVariables
     *
     * @return DynamicRecord|list<DynamicRecord>|null
     */
    public static function provide(Operation $operation, array $uriVariables = []): DynamicRecord|array|null
    {
        $resource = $operation->getClass();
        $items = [
            1 => new DynamicRecord($resource, ['id' => 1, 'question' => 'What is API Platform?', 'answer' => 'A framework.']),
            2 => new DynamicRecord($resource, ['id' => 2, 'question' => 'Is it free?', 'answer' => 'Yes.']),
        ];

        if ($operation instanceof GetCollection) {
            return array_values($items);
        }

        return $items[(int) $uriVariables['id']] ?? null;
    }
}
