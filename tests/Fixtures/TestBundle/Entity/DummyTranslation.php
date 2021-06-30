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

use ApiPlatform\Translation\TranslatableInterface;
use ApiPlatform\Translation\TranslationInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DummyTranslation implements TranslationInterface
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column]
    public string $locale;

    #[ORM\Column(nullable: true)]
    public ?string $name = null;

    #[ORM\Column]
    public string $description;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    public ?DummyTranslatable $translatable;

    public function getTranslatableResource(): TranslatableInterface
    {
        return $this->translatable;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
