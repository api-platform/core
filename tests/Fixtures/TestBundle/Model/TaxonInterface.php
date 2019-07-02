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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     shortName="Taxon",
 *     normalizationContext={
 *         "groups"={"taxon_read"},
 *     },
 *     denormalizationContext={
 *         "groups"={"taxon_write"},
 *     },
 * )
 */
interface TaxonInterface
{
    /**
     * @ApiProperty(identifier=true)
     */
    public function getId();

    /**
     * @Groups({"product_read", "taxon_read"})
     * @Assert\NotBlank
     */
    public function getCode(): ?string;

    /**
     * @Groups({"taxon_write"})
     */
    public function setCode(?string $code): void;
}
