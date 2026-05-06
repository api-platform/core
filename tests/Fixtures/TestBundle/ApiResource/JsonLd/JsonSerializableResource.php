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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'JsonLdJsonSerializable',
    normalizationContext: ['groups' => ['jsonld_jss']],
    operations: [
        new Get(
            uriTemplate: '/jsonld_json_serializables/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_json_serializables',
            provider: [self::class, 'provideNew'],
            processor: [self::class, 'process'],
        ),
    ],
)]
class JsonSerializableResource implements \JsonSerializable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['jsonld_jss'])]
    public ?int $id = null;

    #[Groups(['jsonld_jss'])]
    public string $contentType = '';

    /** @var array<string, string> */
    #[Groups(['jsonld_jss'])]
    public array $fieldValues = [];

    #[Groups(['jsonld_jss'])]
    public JsonSerializableStatus $status;

    public function __construct()
    {
        $this->status = new JsonSerializableStatus('DRAFT', 'draft');
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'contentType' => $this->contentType,
            'status' => $this->status,
            'fieldValues' => $this->fieldValues,
        ];
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->contentType = 'homepage';
        $r->fieldValues = ['title' => 'hello'];

        return $r;
    }

    public static function provideNew(): self
    {
        return new self();
    }

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }
}

final class JsonSerializableStatus implements \JsonSerializable
{
    public function __construct(public readonly string $key, public readonly string $value)
    {
    }

    public function jsonSerialize(): array
    {
        return ['key' => $this->key, 'value' => $this->value];
    }
}
