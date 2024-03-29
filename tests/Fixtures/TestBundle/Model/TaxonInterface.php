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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(shortName: 'Taxon', uriVariables: 'code', normalizationContext: ['groups' => ['taxon_read']], denormalizationContext: ['groups' => ['taxon_write']])]
interface TaxonInterface
{
    public function getId();

    #[ApiProperty(identifier: true)]
    #[Groups(['product_read', 'taxon_read'])]
    #[Assert\NotBlank]
    public function getCode(): ?string;

    #[Groups(['taxon_write'])]
    public function setCode(?string $code): void;
}
