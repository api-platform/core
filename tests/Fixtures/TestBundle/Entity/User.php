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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\PasswordResetRequest;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\PasswordResetRequestResult;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\RecoverPasswordInput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A User.
 *
 * @ORM\Entity
 * @ORM\Table(name="user_test")
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"user", "user-read"}},
 *         "denormalization_context"={"groups"={"user", "user-write"}}
 *     },
 *     collectionOperations={
 *         "post",
 *         "get",
 *         "post_password_reset_request"={
 *             "method"="POST",
 *             "path"="/users/password_reset_request",
 *             "messenger"="input",
 *             "input"=PasswordResetRequest::class,
 *             "output"=PasswordResetRequestResult::class,
 *             "normalization_context"={
 *                 "groups"={"user_password_reset_request"},
 *             },
 *             "denormalization_context"={
 *                 "groups"={"user_password_reset_request"},
 *             },
 *         },
 *     },
 *     itemOperations={"get", "put", "delete",
 *         "recover_password"={
 *             "input"=RecoverPasswordInput::class, "output"=RecoverPasswordOutput::class, "method"="PUT", "path"="users/recover/{id}"
 *         }
 *     }
 * )
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class User extends BaseUser
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @Groups({"user"})
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"user"})
     */
    protected $fullname;

    /**
     * @var string
     *
     * @Groups({"user-write"})
     */
    protected $plainPassword;

    /**
     * @var string
     *
     * @Groups({"user"})
     */
    protected $username;

    /**
     * @param string|null $fullname
     *
     * @return $this
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * {@inheritdoc}
     */
    public function isUser(UserInterface $user = null)
    {
        return $user instanceof self && $user->id === $this->id;
    }
}
