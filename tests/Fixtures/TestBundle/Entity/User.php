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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequest;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequestResult;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\State\RecoverPasswordProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A User.
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(operations: [
    new Post(
        uriTemplate: '/users/password_reset_request',
        messenger: 'input',
        input: PasswordResetRequest::class,
        output: PasswordResetRequestResult::class,
        normalizationContext: ['groups' => ['user_password_reset_request']],
        denormalizationContext: ['groups' => ['user_password_reset_request']]
    ),
    new Put(input: RecoverPasswordInput::class, output: RecoverPasswordOutput::class, uriTemplate: 'users/recover/{id}', processor: RecoverPasswordProcessor::class),
    new Get(),
    new Put(),
    new Delete(),
    new Post(),
    new GetCollection(),
    new Get('users-with-groups/{id}', normalizationContext: ['groups' => ['api-test-case-group']]),
    new GetCollection('users-with-groups', normalizationContext: ['groups' => ['api-test-case-group']]),
], normalizationContext: ['groups' => ['user', 'user-read']], denormalizationContext: ['groups' => ['user', 'user-write']])]
#[ORM\Entity]
#[ORM\Table(name: 'user_test')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[Groups(['user'])]
    private ?string $email = null;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user', 'api-test-case-group'])]
    private ?string $fullname = null;
    #[Groups(['user-write'])]
    private ?string $plainPassword = null;

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
