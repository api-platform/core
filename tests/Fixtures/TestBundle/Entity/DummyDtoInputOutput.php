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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\InputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy InputOutput.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={"input"=InputDto::class, "output"=OutputDto::class})
 * @ORM\Entity
 */
class DummyDtoInputOutput
{
    /**
     * @var int The id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    public $str;

    /**
     * @var int
     * @ORM\Column(type="float")
     */
    public $num;
}
