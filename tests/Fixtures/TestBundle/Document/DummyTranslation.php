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

use ApiPlatform\Translation\TranslatableInterface;
use ApiPlatform\Translation\TranslationInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class DummyTranslation implements TranslationInterface
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public ?int $id = null;

    #[ODM\Field]
    public string $locale;

    #[ODM\Field]
    public string $name;

    #[ODM\Field]
    public string $description;

    #[ODM\ReferenceOne(targetDocument: DummyTranslatable::class, inversedBy: 'translations')]
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
