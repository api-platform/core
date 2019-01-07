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
 * DummyDtoNoInput.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @ORM\Entity
 *
 * @ApiResource(
 *     attributes={
 *         "input_class"=false,
 *         "output_class"=OutputDto::class
 *     }
 * )
 */
class DummyDtoNoInput
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
     * @var string
     *
     * @ORM\Column
     */
    public $lorem;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
