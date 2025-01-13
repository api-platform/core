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

namespace ApiPlatform\Doctrine\Odm\State;

use ApiPlatform\Doctrine\Common\State\Options as CommonOptions;
use ApiPlatform\State\OptionsInterface;

class Options extends CommonOptions implements OptionsInterface
{
    /**
     * @param mixed $handleLinks           experimental callable, typed mixed as we may want a service name in the future
     * @param mixed $transformFromDocument experimental callable, typed mixed as we may want a service name in the future
     *
     * @see LinksHandlerInterface
     */
    public function __construct(
        protected ?string $documentClass = null,
        mixed $handleLinks = null,
        mixed $transformFromDocument = null,
    ) {
        parent::__construct(handleLinks: $handleLinks, toResourceTransformer: $transformFromDocument);
    }

    public function getDocumentClass(): ?string
    {
        return $this->documentClass;
    }

    public function withDocumentClass(?string $documentClass): self
    {
        $self = clone $this;
        $self->documentClass = $documentClass;

        return $self;
    }

    public function getTransformDocument(): mixed
    {
        return $this->getToResourceTransformer();
    }

    public function withTransformDocument(mixed $transformDocument): self
    {
        $self = clone $this;
        $self->toResourceTransformer = $transformDocument;

        return $self;
    }
}
