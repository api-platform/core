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
use ApiPlatform\Metadata\GraphQl\Mutation;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy with a custom GraphQL mutation resolver.
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 */
#[ODM\Document]
#[ApiResource(graphQlOperations: [
    new Mutation(
        name: 'sum',
        resolver: 'app.graphql.mutation_resolver.dummy_custom',
        normalizationContext: ['groups' => ['result']],
        denormalizationContext: ['groups' => ['sum']]
    ),
    new Mutation(
        name: 'sumNotPersisted',
        resolver: 'app.graphql.mutation_resolver.dummy_custom_not_persisted',
        normalizationContext: ['groups' => ['result']],
        denormalizationContext: ['groups' => ['sum']]
    ),
    new Mutation(name: 'sumNoWriteCustomResult',
        resolver: 'app.graphql.mutation_resolver.dummy_custom_no_write_custom_result',
        normalizationContext: ['groups' => ['result']],
        denormalizationContext: ['groups' => ['sum']],
        write: false
    ),
    new Mutation(name: 'sumOnlyPersist',
        resolver: 'app.graphql.mutation_resolver.dummy_custom_only_persist_document',
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
    /**
     * @var int|null
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;

    /**
     * @var int|null
     */
    #[ODM\Field(type: 'int')]
    private $operandA;

    /**
     * @var int|null
     */
    #[Groups(['sum'])]
     #[ODM\Field(type: 'int', nullable: true)]
    private $operandB;

    /**
     * @var int|null
     */
    #[Groups(['result'])]
    #[ODM\Field(type: 'int', nullable: true)]
    private $result;

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
