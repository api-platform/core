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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiFilter(SearchFilter::class, properties: ['nameConverted.nameConverted' => 'partial'])]
#[ApiResource]
#[ORM\Entity]
class ConvertedOwner
{
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var ConvertedRelated|null
     */
    #[ORM\ManyToOne(targetEntity: ConvertedRelated::class)]
    public $nameConverted;

    public function getId(): ?int
    {
        return $this->id;
    }
}
