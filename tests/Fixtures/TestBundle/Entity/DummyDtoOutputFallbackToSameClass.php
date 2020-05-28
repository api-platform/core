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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDtoDummy;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy InputOutput.
 *
 * @author Daniel West <daniel@silverback.is>
 *
 * @ApiResource(attributes={"output"=OutputDtoDummy::class})
 * @ORM\Entity
 */
class DummyDtoOutputFallbackToSameClass
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
