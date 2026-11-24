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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'runtime_attribute_value')]
class AttributeValue
{
    public const FIELDS = [
        'string' => 'stringValue',
        'enum' => 'stringValue',
        'text' => 'textValue',
        'integer' => 'integerValue',
        'float' => 'floatValue',
        'boolean' => 'booleanValue',
        'datetime' => 'datetimeValue',
        'reference' => 'referenceId',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'string_value', nullable: true)]
    private ?string $stringValue = null;

    #[ORM\Column(name: 'text_value', type: 'text', nullable: true)]
    private ?string $textValue = null;

    #[ORM\Column(name: 'integer_value', nullable: true)]
    private ?int $integerValue = null;

    #[ORM\Column(name: 'float_value', nullable: true)]
    private ?float $floatValue = null;

    #[ORM\Column(name: 'boolean_value', nullable: true)]
    private ?bool $booleanValue = null;

    #[ORM\Column(name: 'datetime_value', nullable: true)]
    private ?\DateTimeImmutable $datetimeValue = null;

    #[ORM\Column(name: 'reference_id', nullable: true)]
    private ?int $referenceId = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'values')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private EntityRow $entity,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private AttributeDefinition $attribute,
        #[ORM\Column]
        private int $position,
        mixed $value,
    ) {
        $this->{self::FIELDS[$attribute->getType()]} = $value;
    }

    public function getAttribute(): AttributeDefinition
    {
        return $this->attribute;
    }

    public function getValue(): mixed
    {
        return $this->{self::FIELDS[$this->attribute->getType()]};
    }
}
