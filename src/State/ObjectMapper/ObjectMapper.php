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

use Symfony\Component\ObjectMapper\ObjectMapperAwareInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ObjectMapper implements ObjectMapperInterface, ClearObjectMapInterface
{
    private ?\SplObjectStorage $objectMap = null;

    public function __construct(private ObjectMapperInterface $decorated)
    {
        if (null === $this->objectMap) {
            $this->objectMap = new \SplObjectStorage();
        }

        if ($this->decorated instanceof ObjectMapperAwareInterface) {
            $this->decorated = $this->decorated->withObjectMapper($this);
        }
    }

    public function map(object $source, object|string|null $target = null): object
    {
        if (!\is_object($target) && isset($this->objectMap[$source])) {
            $target = $this->objectMap[$source];
        }
        $mapped = $this->decorated->map($source, $target);
        $this->objectMap[$mapped] = $source;

        return $mapped;
    }

    public function clearObjectMap(): void
    {
        foreach ($this->objectMap as $k) {
            $this->objectMap->detach($k);
        }
    }
}
