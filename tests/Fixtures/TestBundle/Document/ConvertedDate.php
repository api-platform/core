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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiFilter(DateFilter::class)]
#[ApiResource]
#[ODM\Document]
class ConvertedDate
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var \DateTime
     */
    #[ODM\Field(type: 'date')]
    public $nameConverted;

    public function getId(): ?int
    {
        return $this->id;
    }
}
