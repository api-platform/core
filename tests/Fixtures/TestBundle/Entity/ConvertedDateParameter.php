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

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tests that legacy AbstractFilter subclasses work with QueryParameter
 * when a nameConverter is configured (issue #7866).
 */
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'nameConverted' => new QueryParameter(
                    filter: new DateFilter(),
                    properties: ['nameConverted'],
                ),
            ],
        ),
    ],
)]
#[ORM\Entity]
class ConvertedDateParameter
{
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    public \DateTime $nameConverted;

    public function getId(): ?int
    {
        return $this->id;
    }
}
