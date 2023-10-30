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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Tests\Fixtures\TestBundle\Model\ProductInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\TaxonInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Product implements ProductInterface
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     */
    private $code;

    /**
     * @var Taxon|null
     *
     * @ORM\ManyToOne(targetEntity=Taxon::class)
     */
    private $mainTaxon;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getMainTaxon(): ?TaxonInterface
    {
        return $this->mainTaxon;
    }

    public function setMainTaxon(?TaxonInterface $mainTaxon): void
    {
        if (!$mainTaxon instanceof Taxon) {
            throw new \InvalidArgumentException(sprintf('$mainTaxon must be of type "%s".', Taxon::class));
        }

        $this->mainTaxon = $mainTaxon;
    }
}
