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

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ORM\Entity]
class Comment
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;
    #[ORM\Column(type: 'text')]
    public $comment;
    #[ORM\Column(type: 'datetime')]
    public $date;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public $author;

    public function getId()
    {
        return $this->id;
    }
}
