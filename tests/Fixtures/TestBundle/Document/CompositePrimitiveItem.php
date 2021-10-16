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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Composite Primitive Item.
 *
 * @ApiResource
 * @ODM\Document
 */
class CompositePrimitiveItem
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @ODM\Field(type="int")
     */
    private $year;

    /**
     * @ODM\Field(type="string")
     */
    private $description;

    public function __construct(string $name, int $year)
    {
        $this->name = $name;
        $this->year = $year;
    }

    /**
     * Gets id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Gets year.
     */
    public function getYear(): ?int
    {
        return $this->year;
    }

    /**
     * Sets description.
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Gets description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
