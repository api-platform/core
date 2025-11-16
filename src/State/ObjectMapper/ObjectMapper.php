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

namespace ApiPlatform\State\ObjectMapper;

use ApiPlatform\Metadata\Exception\RuntimeException;
use Symfony\Component\ObjectMapper\ObjectMapperAwareInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

if (false === class_exists(ObjectMapperAwareInterface::class)) {
    final class ObjectMapper implements ObjectMapperInterface
    {
        public function __construct(private ObjectMapperInterface $decorated)
        {
        }

        public function map(object $source, object|string|null $target = null): object
        {
            return $this->decorated->map($source, $target);
        }
    }
} else {
    final class ObjectMapper implements ObjectMapperInterface, ObjectMapperAwareInterface
    {
        public function __construct(private ObjectMapperInterface $decorated)
        {
            if ($this->decorated instanceof ObjectMapperAwareInterface) {
                $this->decorated = $this->decorated->withObjectMapper($this);
            }
        }

        public function map(object $source, object|string|null $target = null): object
        {
            return $this->decorated->map($source, $target);
        }

        public function withObjectMapper(ObjectMapperInterface $objectMapper): static
        {
            $s = clone $this;

            if (!$s->decorated instanceof ObjectMapperAwareInterface) {
                throw new RuntimeException(\sprintf('Given object mapper "%s" does not implements %s.', get_debug_type($this->decorated), ObjectMapperAwareInterface::class));
            }

            $s->decorated->withObjectMapper($objectMapper);

            return $s;
        }
    }
}
