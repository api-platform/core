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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\SearchFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource
 * @ODM\Document
 * @ApiFilter(SearchFilter::class, properties={"nameConverted.nameConverted"="partial"})
 */
class ConvertedOwner
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var ConvertedRelated
     *
     * @ODM\ReferenceOne(targetDocument=ConvertedRelated::class, storeAs="id", nullable=true)
     */
    public $nameConverted;

    public function getId()
    {
        return $this->id;
    }
}
