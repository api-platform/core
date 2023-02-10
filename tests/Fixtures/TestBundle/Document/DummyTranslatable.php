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
use ApiPlatform\Translation\TranslatableInterface;
use ApiPlatform\Translation\TranslationInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => 'read', 'jsonld_embed_context' => true],
    translation: [
        'class' => DummyTranslation::class,
        'allTranslationsClientEnabled' => true,
        'allTranslationsClientParameterName' => 'allTranslations',
    ],
)]
#[ODM\Document]
class DummyTranslatable implements TranslatableInterface
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public ?int $id = null;

    #[ODM\Field]
    #[Groups('read')]
    #[ApiProperty(jsonldContext: ['@container' => null])]
    public string $notTranslatedField;

    #[Groups('read')]
    public string $name;

    #[Groups('read')]
    public string $description;

    /**
     * @var Collection<DummyTranslation>
     */
    #[ODM\ReferenceMany(targetDocument: DummyTranslation::class, mappedBy: 'translatable', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(DummyTranslation $translation): void
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->translatable = $this;
        }
    }

    public function removeTranslation(DummyTranslation $translation): void
    {
        $this->getTranslations()->removeElement($translation);
        $translation->translatable = null;
    }

    public function getResourceTranslation(string $locale): ?TranslationInterface
    {
        foreach ($this->getTranslations() as $translation) {
            if ($locale === $translation->getLocale()) {
                return $translation;
            }
        }

        return null;
    }

    public function getResourceTranslations(): iterable
    {
        return $this->getTranslations();
    }

    public function addResourceTranslation(TranslationInterface $translation): void
    {
        if ($translation instanceof DummyTranslation) {
            $this->addTranslation($translation);
        }
    }

    public function removeResourceTranslation(TranslationInterface $translation): void
    {
        if ($translation instanceof DummyTranslation) {
            $this->removeTranslation($translation);
        }
    }
}
