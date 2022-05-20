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

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Dummy with a custom GraphQL mutation resolver.
 *
 * @ORM\Entity
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
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom_only_persist",
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
 *     },
 *     "testOutput"={
 *         "mutation"="app.graphql.mutation_resolver.dummy_custom",
 *         "output"=OutputDto::class
 *     }
 * })
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 */
class DummyCustomMutation
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $operandA;

    /**
     * @var int|null
     *
     * @Groups({"sum"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private $operandB;

    /**
     * @var int|null
     *
     * @Groups({"result"})
     * @ORM\Column(type="integer", nullable=true)
     */
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
