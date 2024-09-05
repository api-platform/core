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

namespace ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;

/**
 * DummyNoGetOperation.
 *
 * @author Grégoire Hébert gregoire@les-tilleuls.coop
 */
#[ApiResource(operations: [new Put(), new Post()])]
#[ORM\Entity]
class DummyNoGetOperation
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    /**
     * @var string
     */
    #[ORM\Column]
    public $lorem;

    public function setId($id): void
    {
        $this->id = $id;
    }
}
