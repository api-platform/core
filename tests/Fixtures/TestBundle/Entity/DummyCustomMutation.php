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
use ApiPlatform\Metadata\GraphQl\Mutation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy with a custom GraphQL mutation resolver.
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 */
#[ORM\Entity]
#[ApiResource(graphQlOperations: [
    new Mutation(
        name: 'sum',
        resolver: 'app.graphql.mutation_resolver.dummy_custom',
        extraArgs: ['id' => ['type' => 'ID!']],
        normalizationContext: ['groups' => ['result']],
        denormalizationContext: ['groups' => ['sum']]
    ),
    new Mutation(
        name: 'sumNotPersisted',
        resolver: 'app.graphql.mutation_resolver.dummy_custom_not_persisted',
        extraArgs: ['id' => ['type' => 'ID!']],
        normalizationContext: ['groups' => ['result']],
        denormalizationContext: ['groups' => ['sum']]
    ),
    new Mutation(name: 'sumNoWriteCustomResult',
        resolver: 'app.graphql.mutation_resolver.dummy_custom_no_write_custom_result',
        extraArgs: ['id' => ['type' => 'ID!']],
        normalizationContext: ['groups' => ['result']],
        denormalizationContext: ['groups' => ['sum']],
        write: false
    ),
    new Mutation(name: 'sumOnlyPersist',
        resolver: 'app.graphql.mutation_resolver.dummy_custom_only_persist',
        extraArgs: ['id' => ['type' => 'ID!']],
        normalizationContext: ['groups' => ['result']],
        denormalizationContext: ['groups' => ['sum']],
        read: false,
        deserialize: false,
        validate: false,
        serialize: false
    ),
    new Mutation(name: 'testCustomArguments',
        resolver: 'app.graphql.mutation_resolver.dummy_custom',
        args: ['operandC' => ['type' => 'Int!']]
    ),
])]
class DummyCustomMutation
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $operandA = null;

    #[Groups(['sum'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $operandB = null;

    #[Groups(['result'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $result = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperandA(): ?int
    {
        return $this->operandA;
    }

    public function setOperandA(int $operandA): void
    {
        $this->operandA = $operandA;
    }

    public function getOperandB(): ?int
    {
        return $this->operandB;
    }

    public function setOperandB(int $operandB): void
    {
        $this->operandB = $operandB;
    }

    public function getResult(): ?int
    {
        return $this->result;
    }

    public function setResult(int $result): void
    {
        $this->result = $result;
    }
}
