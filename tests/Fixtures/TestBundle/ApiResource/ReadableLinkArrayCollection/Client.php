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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ReadableLinkArrayCollection;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'ReadableLinkArrayCollectionClient',
    operations: [
        new Get(
            uriTemplate: '/readable_link_array_collection_clients/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class Client
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    #[ApiProperty(readableLink: false)]
    public ?Api $singleApi = null;

    private array $typedExchangeApis = [];

    private array $untypedExchangeApis = [];

    /**
     * @return list<Api>
     */
    #[ApiProperty(readableLink: false)]
    public function getTypedExchangeApis(): array
    {
        return $this->typedExchangeApis;
    }

    /**
     * @param list<Api> $typedExchangeApis
     */
    public function setTypedExchangeApis(array $typedExchangeApis): void
    {
        $this->typedExchangeApis = $typedExchangeApis;
    }

    #[ApiProperty(readableLink: false)]
    public function getUntypedExchangeApis(): array
    {
        return $this->untypedExchangeApis;
    }

    public function setUntypedExchangeApis(array $untypedExchangeApis): void
    {
        $this->untypedExchangeApis = $untypedExchangeApis;
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $client = new self();
        $client->id = (int) ($uriVariables['id'] ?? 1);
        $client->singleApi = new Api(2, 'single');
        $client->setTypedExchangeApis([
            new Api(3, 'exchange-a'),
            new Api(4, 'exchange-b'),
        ]);
        $client->setUntypedExchangeApis([
            new Api(5, 'exchange-c'),
            new Api(6, 'exchange-d'),
        ]);

        return $client;
    }
}
