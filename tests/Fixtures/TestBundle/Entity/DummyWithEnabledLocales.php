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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ORM\Mapping as ORM;
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
 *      "api_dummy_with_enabled_locales_sub_resource_dummy_get_subresource" = {"method"="GET", "enabled_locales"={"be", "ru"}},
 *      "api_dummy_with_enabled_locales_sub_resource_dummy_get_subresource_locale" = {"path"="/{_locale}/dummy_with_enabled_locales/{id}/sub_resource_dummy", "method"="GET", "enabled_locales"={"be", "ru"}}
 *     }
 * )
 * @ORM\Entity
 */
class DummyWithEnabledLocales
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var DummyWithEnabledLocales A related dummy set as subResource
     *
     * @ORM\OneToOne(targetEntity="DummyWithEnabledLocales", cascade={"persist"})
     * @ApiSubresource()
     */
    private $subResourceDummy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id)
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
