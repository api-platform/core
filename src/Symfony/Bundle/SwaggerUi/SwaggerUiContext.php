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

namespace ApiPlatform\Symfony\Bundle\SwaggerUi;

final class SwaggerUiContext
{
    /**
     * @param string|null $assetPackage
     */
    public function __construct(private readonly bool $swaggerUiEnabled = false, private readonly bool $showWebby = true, private readonly bool $reDocEnabled = false, private readonly bool $graphQlEnabled = false, private readonly bool $graphiQlEnabled = false, private $assetPackage = null, private readonly array $extraConfiguration = [])
    {
    }

    public function isSwaggerUiEnabled(): bool
    {
        return $this->swaggerUiEnabled;
    }

    public function isWebbyShown(): bool
    {
        return $this->showWebby;
    }

    public function isRedocEnabled(): bool
    {
        return $this->reDocEnabled;
    }

    public function isGraphQlEnabled(): bool
    {
        return $this->graphQlEnabled;
    }

    public function isGraphiQlEnabled(): bool
    {
        return $this->graphiQlEnabled;
    }

    public function getAssetPackage(): ?string
    {
        return $this->assetPackage;
    }

    public function getExtraConfiguration(): array
    {
        return $this->extraConfiguration;
    }
}
