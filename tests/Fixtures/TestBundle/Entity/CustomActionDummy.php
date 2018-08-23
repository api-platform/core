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
 * @ORM\Entity
 * @ApiResource(itemOperations={
 *     "get",
 *     "get_custom"={"method"="GET", "path"="custom_action_collection_dummies/{id}"},
 *     "custom_normalization"={"route_name"="custom_normalization"},
 *     "short_custom_normalization",
 * },
 * collectionOperations={
 *     "get",
 *     "get_custom"={"method"="GET", "path"="custom_action_collection_dummies"},
 *     "custom_denormalization"={"route_name"="custom_denormalization"},
 *     "short_custom_denormalization",
 * })
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomActionDummy
{
    /**
     * @var int
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
    private $foo = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $foo)
    {
        $this->foo = $foo;
    }
}
