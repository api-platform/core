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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Iterable.
 *
 * @author Julien Verger <julien.verger@gmail.com>
 *
 * @ApiResource
 * @ORM\Entity
 */
class DummyIterable
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string[]
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public $arrayOfString;

    /**
     * @var int[]
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public $arrayOfInt;

    /**
     * @var float[]
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public $arrayOfFloat;

    /**
     * @var bool[]
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public $arrayOfBoolean;

    /**
     * @var array
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public $arrayData;

    public function getId()
    {
        return $this->id;
    }

    public function getArrayOfString(): array
    {
        return $this->arrayOfString;
    }

    public function setArrayOfString(array $arrayOfString): void
    {
        $this->arrayOfString = $arrayOfString;
    }

    public function getArrayOfInt(): array
    {
        return $this->arrayOfInt;
    }

    public function setArrayOfInt(array $arrayOfInt): void
    {
        $this->arrayOfInt = $arrayOfInt;
    }

    public function getArrayOfFloat(): array
    {
        return $this->arrayOfFloat;
    }

    public function setArrayOfFloat(array $arrayOfFloat): void
    {
        $this->arrayOfFloat = $arrayOfFloat;
    }

    public function getArrayOfBoolean(): array
    {
        return $this->arrayOfBoolean;
    }

    public function setArrayOfBoolean(array $arrayOfBoolean): void
    {
        $this->arrayOfBoolean = $arrayOfBoolean;
    }

    public function setArrayData($arrayData)
    {
        $this->arrayData = $arrayData;
    }

    public function getArrayData()
    {
        return $this->arrayData;
    }
}
