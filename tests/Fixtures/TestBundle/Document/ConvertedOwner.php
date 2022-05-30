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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiFilter(SearchFilter::class, properties: ['nameConverted.nameConverted' => 'partial'])]
#[ApiResource]
#[ODM\Document]
class ConvertedOwner
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var ConvertedRelated
     */
    #[ODM\ReferenceOne(targetDocument: ConvertedRelated::class, storeAs: 'id', nullable: true)]
    public $nameConverted;

    public function getId()
    {
        return $this->id;
    }
}
