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
 * DisableItemOperation.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @ApiResource(itemOperations={}, collectionOperations={"get", "post"})
 * @ORM\Entity
 */
class DisableItemOperation
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     */
    public $name;

    public function getId() {
        return $this->id;
    }
}
