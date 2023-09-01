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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Date Immutable.
 */
#[ApiResource(filters: ['my_dummy_immutable_date.mongodb.date'])]
#[ODM\Document]
class DummyImmutableDate
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var \DateTimeImmutable The dummy date
     */
    #[ODM\Field(type: 'date_immutable')]
    public $dummyDate;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
