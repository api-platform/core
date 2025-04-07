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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequest;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequestResult;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A User.
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(openapi: false, operations: [new Get(), new Put(), new Delete(), new Put(input: RecoverPasswordInput::class, output: RecoverPasswordOutput::class, uriTemplate: 'users/recover/{id}'), new Post(), new GetCollection(), new Post(uriTemplate: '/users/password_reset_request', messenger: 'input', input: PasswordResetRequest::class, output: PasswordResetRequestResult::class, normalizationContext: ['groups' => ['user_password_reset_request']], denormalizationContext: ['groups' => ['user_password_reset_request']])], normalizationContext: ['groups' => ['user', 'user-read']], denormalizationContext: ['groups' => ['user', 'user-write']])]
#[ODM\Document(collection: 'user_test')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    protected ?int $id = null;
    #[Groups(['user'])]
    protected ?string $email = null;
    #[Groups(['user'])]
    #[ODM\Field(type: 'string', nullable: true)]
    protected ?string $fullname = null;
    #[Groups(['user-write'])]
    protected ?string $plainPassword = null;
    /**
     * @var string|null
     */
    #[Groups(['user'])]
    protected $username;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function setFullname(?string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }
}
