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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDto;
use Doctrine\ORM\Mapping as ORM;

/**
 * DummyDtoNoOutput.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(input: InputDto::class, output: false)]
#[ORM\Entity]
class DummyDtoNoOutput
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var string
     */
    #[ORM\Column]
    public $lorem;
    /**
     * @var string
     */
    #[ORM\Column]
    public $ipsum;

    public function getId(): ?int
    {
        return $this->id;
    }
}
