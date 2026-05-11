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
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'JsonLdInterfaceTaxon',
    normalizationContext: ['groups' => ['jsonld_taxon_read']],
    operations: [
        new Get(
            uriTemplate: '/jsonld_interface_taxa/{code}',
            uriVariables: ['code'],
            provider: [InterfaceTaxonImpl::class, 'provideTaxon'],
        ),
    ],
)]
interface InterfaceTaxon
{
    #[ApiProperty(identifier: true)]
    #[Groups(['jsonld_taxon_read', 'jsonld_product_read'])]
    public function getCode(): ?string;
}

final class InterfaceTaxonImpl implements InterfaceTaxon
{
    public function __construct(public string $code = '')
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public static function provideTaxon(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self($uriVariables['code'] ?? 'WONDERFUL_TAXON');
    }
}
