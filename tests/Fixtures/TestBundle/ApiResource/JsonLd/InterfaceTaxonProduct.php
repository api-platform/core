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
    shortName: 'JsonLdInterfaceTaxonProduct',
    normalizationContext: ['groups' => ['jsonld_product_read']],
    operations: [
        new Get(
            uriTemplate: '/jsonld_interface_taxon_products/{code}',
            uriVariables: ['code'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class InterfaceTaxonProduct
{
    #[ApiProperty(identifier: true)]
    #[Groups(['jsonld_product_read'])]
    public string $code;

    #[Groups(['jsonld_product_read'])]
    public ?InterfaceTaxon $mainTaxon = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $product = new self();
        $product->code = $uriVariables['code'] ?? 'GREAT_PRODUCT';
        $product->mainTaxon = new InterfaceTaxonImpl('WONDERFUL_TAXON');

        return $product;
    }
}
