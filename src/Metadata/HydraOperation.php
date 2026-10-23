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

namespace ApiPlatform\Metadata;

/**
 * References an operation declared on the resource to expose it in the "hydra:operation" property of the JSON-LD responses.
 *
 * The operation is referenced either by its name, or by its method and URI template (the format suffix is ignored).
 * Everything else (title, description, expected and returned types) is read from the referenced operation.
 */
final class HydraOperation
{
    /**
     * @param string|null             $method      the HTTP method of the referenced operation
     * @param string|null             $uriTemplate the URI template of the referenced operation, defaults to the one of the operation declaring the reference
     * @param string|null             $name        the name of the referenced operation
     * @param string|\Stringable|null $security    decides when the operation is exposed, defaults to the security of the referenced operation
     */
    public function __construct(
        private readonly ?string $method = null,
        private readonly ?string $uriTemplate = null,
        private readonly ?string $name = null,
        private readonly string|\Stringable|null $security = null,
    ) {
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getUriTemplate(): ?string
    {
        return $this->uriTemplate;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSecurity(): ?string
    {
        return $this->security instanceof \Stringable ? (string) $this->security : $this->security;
    }
}
