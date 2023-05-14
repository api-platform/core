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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Overridden Operation Dummy.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
#[ApiResource(operations: [new Get(), new Get(normalizationContext: ['groups' => ['overridden_operation_dummy_get']], denormalizationContext: ['groups' => ['overridden_operation_dummy_get']]), new Put(normalizationContext: ['groups' => ['overridden_operation_dummy_put']], denormalizationContext: ['groups' => ['overridden_operation_dummy_put']]), new Delete(), new GetCollection(), new Post(), new GetCollection(uriTemplate: '/override/swagger')], normalizationContext: ['groups' => ['overridden_operation_dummy_read']], denormalizationContext: ['groups' => ['overridden_operation_dummy_write']])]
#[ODM\Document]
class OverriddenOperationDummy
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    /**
     * @var string The dummy name
     */
    #[Assert\NotBlank]
    #[Groups(['overridden_operation_dummy_read', 'overridden_operation_dummy_write', 'overridden_operation_dummy_get'])]
    #[ApiProperty(types: ['https://schema.org/name'])]
    #[ODM\Field]
    private $name;

    /**
     * @var string The dummy name alias
     */
    #[ApiProperty(types: ['https://schema.org/alternateName'])]
    #[Groups(['overridden_operation_dummy_read', 'overridden_operation_dummy_put', 'overridden_operation_dummy_get'])]
    #[ODM\Field(nullable: true)]
    private $alias;

    /**
     * @var string A short description of the item
     */
    #[ApiProperty(types: ['https://schema.org/description'])]
    #[Groups(['overridden_operation_dummy_read', 'overridden_operation_dummy_write', 'overridden_operation_dummy_get', 'overridden_operation_dummy_put'])]
    #[ODM\Field(nullable: true)]
    public $description;

    #[Groups(['overridden_operation_dummy_write'])]
    #[ODM\Field(nullable: true)]
    public $notGettable;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAlias($alias): void
    {
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
