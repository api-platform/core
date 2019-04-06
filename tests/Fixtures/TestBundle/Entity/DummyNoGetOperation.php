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
 * DummyNoGetOperation.
 *
 * @author Grégoire Hébert gregoire@les-tilleuls.coop
 *
 * @ORM\Entity
 *
 * @ApiResource(
 *     collectionOperations={"post"},
 *     itemOperations={"put"}
 * )
 */
class DummyNoGetOperation
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

    public function setId($id)
    {
        $this->id = $id;
    }
}
