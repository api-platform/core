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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\State\ResourceObjectResolverInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedBackingGadget;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedBackingThing;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SharedBackingEntity;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class SharedBackingResourceObjectResolver implements ResourceObjectResolverInterface
{
    public function __construct(
        private ResourceObjectResolverInterface $inner,
        private ObjectMapperInterface $objectMapper,
    ) {
    }

    public function resolve(object $data): iterable
    {
        if (!$data instanceof SharedBackingEntity) {
            return $this->inner->resolve($data);
        }

        return [$this->objectMapper->map($data, 'thing' === $data->kind ? SharedBackingThing::class : SharedBackingGadget::class)];
    }
}
