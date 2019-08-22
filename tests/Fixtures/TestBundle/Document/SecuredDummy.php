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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Secured resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_USER')"},
 *     collectionOperations={
 *         "get",
 *         "get_from_data_provider_generator"={
 *             "method"="GET",
 *             "path"="custom_data_provider_generator",
 *             "security"="is_granted('ROLE_USER')"
 *         },
 *         "post"={"security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_USER') and object.getOwner() == user"},
 *         "put"={"security_post_denormalize"="is_granted('ROLE_USER') and previous_object.getOwner() == user"},
 *     },
 *     graphql={
 *         "query"={"security"="is_granted('ROLE_USER') and object.getOwner() == user"},
 *         "delete"={},
 *         "update"={"security_post_denormalize"="is_granted('ROLE_USER') and previous_object.getOwner() ==  user"},
 *         "create"={"security"="is_granted('ROLE_ADMIN')", "security_message"="Only admins can create a secured dummy."}
 *     }
 * )
 * @ODM\Document
 */
class SecuredDummy
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string The title
     *
     * @ODM\Field
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var string The description
     *
     * @ODM\Field
     */
    private $description = '';

    /**
     * @var string The owner
     *
     * @ODM\Field
     * @Assert\NotBlank
     */
    private $owner;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner)
    {
        $this->owner = $owner;
    }
}
