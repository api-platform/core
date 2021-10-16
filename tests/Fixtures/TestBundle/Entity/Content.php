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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Enum\ContentStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"get_content"},
 *     },
 * )
 *
 * @ORM\Entity
 */
class Content implements \JsonSerializable
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
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    private $contentType;

    /**
     * @var Collection<Field>
     *
     * @ORM\OneToMany(
     *     targetEntity=Field::class,
     *     mappedBy="content",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     indexBy="name",
     * )
     * @ORM\OrderBy({"id"="ASC"})
     */
    private $fields;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $status;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->status = ContentStatus::DRAFT;
    }

    /**
     * @Groups({"get_content"})
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Groups({"get_content"})
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * @return array<string, Field>
     */
    public function getFields(): array
    {
        return $this->fields->toArray();
    }

    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    public function addField(Field $field): void
    {
        if ($this->hasField($field->getName())) {
            throw new \InvalidArgumentException(sprintf("Content already has '%s' field", $field->getName()));
        }

        $this->fields[$field->getName()] = $field;
        $field->setContent($this);
    }

    public function removeField(Field $field): void
    {
        unset($this->fields[$field->getName()]);

        // set the owning side to null (unless already changed)
        if ($field->getContent() === $this) {
            $field->setContent(null);
        }
    }

    /**
     * @Groups({"get_content"})
     */
    public function getFieldValues(): array
    {
        $fieldValues = [];
        foreach ($this->getFields() as $field) {
            $fieldValues[$field->getName()] = $field->getValue();
        }

        return $fieldValues;
    }

    /**
     * @Groups({"get_content"})
     */
    public function getStatus(): ContentStatus
    {
        return new ContentStatus($this->status);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'contentType' => $this->contentType,
            'fields' => $this->fields,
        ];
    }
}
