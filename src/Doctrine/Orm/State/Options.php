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

namespace ApiPlatform\Doctrine\Orm\State;

use ApiPlatform\Doctrine\Common\State\Options as CommonOptions;
use ApiPlatform\State\OptionsInterface;

class Options extends CommonOptions implements OptionsInterface
{
    /**
     * @param string|callable $handleLinks         experimental callable, typed mixed as we may want a service name in the future
     * @param string|callable $transformFromEntity experimental callable, typed mixed as we may want a service name in the future
     *
     * @see LinksHandlerInterface
     */
    public function __construct(
        protected ?string $entityClass = null,
        mixed $handleLinks = null,
        mixed $transformFromEntity = null,
        mixed $transformToEntity = null,
    ) {
        parent::__construct(handleLinks: $handleLinks, toResourceTransformer: $transformFromEntity, fromResourceTransformer: $transformToEntity);
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function withEntityClass(?string $entityClass): self
    {
        $self = clone $this;
        $self->entityClass = $entityClass;

        return $self;
    }

    public function getTransformFromEntity(): mixed
    {
        return $this->getToResourceTransformer();
    }

    public function withTransformFromEntity(mixed $transformFromEntity): self
    {
        $self = clone $this;
        $self->toResourceTransformer = $transformFromEntity;

        return $self;
    }

    public function getTransformToEntity(): mixed
    {
        return $this->getFromResourceTransformer();
    }

    public function withTransformToEntity(mixed $transformToEntity): self
    {
        $self = clone $this;
        $self->fromResourceTransformer = $transformToEntity;

        return $self;
    }
}
