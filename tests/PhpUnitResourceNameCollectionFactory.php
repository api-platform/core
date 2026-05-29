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

namespace ApiPlatform\Tests;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\Validator\Exception\ValidationException;

/**
 * Replaces the AttributeResourceNameCollectionFactory to speed up tests.
 */
final class PhpUnitResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    /**
     * @param class-string[] $classes
     */
    public function __construct(private readonly string $env, private readonly array $classes, private readonly ResourceNameCollectionFactoryInterface $decorated)
    {
    }

    public function create(): ResourceNameCollection
    {
        if ([] === $this->classes) {
            return $this->decorated->create();
        }

        /* @var array<class-string, bool> */
        $classes = [];
        foreach ($this->classes as $c) {
            if ('mongodb' === $this->env) {
                $c = str_contains($c, 'Entity') ? str_replace('Entity', 'Document', $c) : $c;
            }

            if (!class_exists($c) && !interface_exists($c)) {
                continue;
            }

            $classes[$c] = true;
        }

        $classes[Error::class] = true;
        $classes[ValidationException::class] = true;

        return new ResourceNameCollection(array_keys($classes));
    }
}
