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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy with enabled locales.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @ApiResource(
 *     enabledLocales={"ro","mi"},
 *     collectionOperations={
 *      "get" = {"method"="GET", "enabled_locales"={"pt", "zh"}}
 *     },
 *     itemOperations={
 *      "get" = {"method"="GET", "enabled_locales"={"it", "cz"}},
 *      "fallback" = {"method"="GET", "path"="/dummy_with_enabled_locales_fallback/{id}"},
 *      "withLocaleAttribute" = {"method"="GET", "path"="/{_locale}/dummy_with_enabled_locales_in_request_attribute/{id}"}
 *     },
 *     subresourceOperations={
 *      "api_dummy_with_enabled_locales_sub_resource_dummy_get_subresource" = {"method"="GET", "enabled_locales"={"be", "ru"}}
 *     }
 * )
 *
 * @ODM\Document
 */
class DummyWithEnabledLocales
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field(type="string")
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var DummyWithEnabledLocales A related dummy
     *
     * @ODM\ReferenceOne(targetDocument="DummyWithEnabledLocales", storeAs="id", nullable=true)
     * @ApiSubresource()
     */
    private $subResourceDummy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSubResourceDummy(): ?DummyWithEnabledLocales
    {
        return $this->subResourceDummy;
    }

    public function setSubResourceDummy(?DummyWithEnabledLocales $subResourceDummy): void
    {
        $this->subResourceDummy = $subResourceDummy;
    }
}
