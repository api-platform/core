<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Builder\Annotation\Resource;

/**
 * No collection dummy.
 *
 * @Resource(collectionOperations={})
 * @ORM\Entity
 */
class NoCollectionDummy
{
    /**
     * @var int The id.
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
