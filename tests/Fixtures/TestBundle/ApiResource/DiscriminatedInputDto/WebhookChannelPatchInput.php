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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedInputDto;

/**
 * Mutable (setter-based) discriminated-input subclass, mirroring the real-world shape that a PATCH
 * populates: a nullable, object-typed property declared only on the subclass. The property is
 * written through a setter during object-to-populate denormalization, not the constructor.
 */
final class WebhookChannelPatchInput extends NotificationChannelInput
{
    public function __construct(
        private ?string $type = null,
        private ?WebhookConfig $config = null,
    ) {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getConfig(): ?WebhookConfig
    {
        return $this->config;
    }

    public function setConfig(?WebhookConfig $config): void
    {
        $this->config = $config;
    }
}
