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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DynamicResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\TypeInfo\Type;

final class DynamicResourceDefinitions
{
    public const FAQ = 'DynamicFaq';
    public const NEWS = 'DynamicNews';

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
                    'related' => new ApiProperty(readable: true, writable: false, readableLink: true, nativeType: Type::object(DynamicRecord::class)),
                ],
            ],
            self::NEWS => [
                'uriTemplate' => '/dynamic/news',
                'properties' => [
                    'id' => new ApiProperty(identifier: true, readable: true, writable: false, nativeType: Type::int()),
                    'title' => new ApiProperty(readable: true, writable: true, required: true, nativeType: Type::string()),
                    'body' => new ApiProperty(readable: true, writable: true, nativeType: Type::string()),
                ],
            ],
        ];
    }

    public static function has(string $resource): bool
    {
        return isset(self::all()[$resource]);
    }

    /**
     * @return list<ApiResource>
     */
    public static function resources(): array
    {
        $resources = [];
        foreach (self::all() as $name => $definition) {
            $link = new Link(fromClass: DynamicRecord::class, identifiers: ['id'], parameterName: 'id');
            $resources[] = new ApiResource(
                class: DynamicRecord::class,
                shortName: $name,
                operations: [
                    new Get(class: DynamicRecord::class, shortName: $name, name: $name.'_get', uriTemplate: $definition['uriTemplate'].'/{id}', uriVariables: ['id' => $link], provider: [self::class, 'provide']),
                    new GetCollection(class: DynamicRecord::class, shortName: $name, name: $name.'_get_collection', uriTemplate: $definition['uriTemplate'], provider: [self::class, 'provide']),
                ],
            );
        }

        return $resources;
    }

    public static function resourceForOperation(ResourceMetadataCollectionFactoryInterface $factory, ?string $operationName): ?string
    {
        if (null === $operationName) {
            return null;
        }

        $shortName = $factory->create(DynamicRecord::class)->getOperation($operationName)->getShortName();

        return null !== $shortName && self::has($shortName) ? $shortName : null;
    }

    /**
     * @return array<string, ApiProperty>
     */
    public static function properties(?string $resource): array
    {
        if (null !== $resource) {
            return self::all()[$resource]['properties'];
        }

        $properties = [];
        foreach (self::all() as $definition) {
            $properties += $definition['properties'];
        }

        return $properties;
    }

    /**
     * @param array<string, mixed> $uriVariables
     *
     * @return DynamicRecord|list<DynamicRecord>|null
     */
    public static function provide(Operation $operation, array $uriVariables = []): DynamicRecord|array|null
    {
        $resource = $operation->getShortName();
        $items = match ($resource) {
            self::FAQ => [
                1 => new DynamicRecord($resource, ['id' => 1, 'question' => 'What is API Platform?', 'answer' => 'A framework.', 'related' => new DynamicRecord(self::NEWS, ['id' => 1, 'title' => 'API Platform 5.0', 'body' => 'Released.'])]),
                2 => new DynamicRecord($resource, ['id' => 2, 'question' => 'Is it free?', 'answer' => 'Yes.']),
            ],
            self::NEWS => [
                1 => new DynamicRecord($resource, ['id' => 1, 'title' => 'API Platform 5.0', 'body' => 'Released.']),
                2 => new DynamicRecord($resource, ['id' => 2, 'title' => 'SymfonyCon', 'body' => 'See you there.']),
            ],
            default => [],
        };

        if ($operation instanceof GetCollection) {
            return array_values($items);
        }

        return $items[(int) $uriVariables['id']] ?? null;
    }
}
