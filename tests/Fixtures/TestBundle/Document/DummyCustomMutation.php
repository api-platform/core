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
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy with a custom GraphQL mutation resolver.
 *
 * @ODM\Document
 * @ApiResource(graphql={
 *     "sum"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}}
 *     },
 *     "sumNotPersisted"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_not_persisted",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}}
 *     },
 *     "sumNoWriteCustomResult"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_no_write_custom_result",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}},
 *         "write"=false
 *     },
 *     "sumOnlyPersist"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_only_persist_document",
 *         "normalization_context"={"groups"={"result"}},
 *         "denormalization_context"={"groups"={"sum"}},
 *         "read"=false,
 *         "deserialize"=false,
 *         "validate"=false,
 *         "serialize"=false
 *     },
 *     "testCustomArguments"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom",
 *         "args"={"operandC"={"type"="Int!"}}
 *     }
 * })
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 */
class DummyCustomMutation
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private $operandA;

    /**
     * @var int
     *
     * @Groups({"sum"})
     * @ODM\Field(type="int", nullable=true)
     */
    private $operandB;

    /**
     * @var int
     *
     * @Groups({"result"})
     * @ODM\Field(type="int", nullable=true)
     */
    private $result;

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperandA(): int
    {
        return $this->operandA;
    }

    public function setOperandA(int $operandA): void
    {
        $this->operandA = $operandA;
    }

    public function getOperandB(): int
    {
        return $this->operandB;
    }

    public function setOperandB(int $operandB): void
    {
        $this->operandB = $operandB;
    }

    public function getResult(): int
    {
        return $this->result;
    }

    public function setResult(int $result): void
    {
        $this->result = $result;
    }
}
