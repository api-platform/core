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
 * Dummy InputOutput.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={"output"=DummyDtoOutputSameClass::class})
 * @ORM\Entity
 */
class DummyDtoOutputSameClass
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
     * @var string
     *
     * @ORM\Column
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
